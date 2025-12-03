import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Trend } from 'k6/metrics';

// ============================================================
// MÉTRIQUES PERSONNALISÉES
// ============================================================
// Note: On n'utilise plus de métrique 'errors' custom car le dashboard
// Grafana utilise la métrique standard 'http_req_failed' de K6
const listTime = new Trend('list_duration');
const detailTime = new Trend('detail_duration');
const createTime = new Trend('create_duration');
const deleteTime = new Trend('delete_duration');

// ============================================================
// CONFIGURATION DU TEST
// ============================================================
export let options = {
    stages: [
        { duration: '5s', target: 5 },   // Montée progressive
        { duration: '10s', target: 10 },   // Charge normale
        { duration: '10s', target: 50 },   // Pic de charge
        { duration: '5s', target: 10 },   // Retour normal
        { duration: '5s', target: 5 },    // Arrêt
    ],
    thresholds: {
        'http_req_duration': ['p(95)<500'],
        'http_req_failed': ['rate<0.05'],
    },
};

// URL de base (nom du container Docker)
const BASE_URL = 'http://tpmongo-php:80';

// Livres de test
const LIVRES = [
    { title: 'Les Misérables', author: 'Victor Hugo', century: '19', cote: 'LM-001', langue: 'Français', edition: 'Gallimard' },
    { title: 'Le Petit Prince', author: 'Saint-Exupéry', century: '20', cote: 'PP-001', langue: 'Français', edition: 'Folio' },
    { title: 'Germinal', author: 'Émile Zola', century: '19', cote: 'GE-001', langue: 'Français', edition: 'Pocket' },
    { title: 'Candide', author: 'Voltaire', century: '18', cote: 'CA-001', langue: 'Français', edition: 'Larousse' },
];

// ============================================================
// FONCTION UTILITAIRE : Extraire un ID depuis le HTML
// ============================================================
function extractId(html) {
    const match = html.match(/id=([a-f0-9]{24})/);
    return match ? match[1] : null;
}

// ============================================================
// SCÉNARIO PRINCIPAL
// ============================================================
export default function () {

    // 80% lecture, 15% création, 5% suppression
    const rand = Math.random();

    if (rand < 0.80) {
        // ========== SCÉNARIO LECTURE (80%) ==========
        group('Lecture', function () {

            // 1. Affichage liste des livres
            let res = http.get(`${BASE_URL}/app.php`);
            check(res, { 'Liste OK': (r) => r.status === 200 });
            listTime.add(res.timings.duration);

            sleep(1);

            // 2. Pagination (page aléatoire 1-5)
            const page = Math.floor(Math.random() * 5) + 1;
            res = http.get(`${BASE_URL}/app.php?page=${page}`);
            check(res, { 'Pagination OK': (r) => r.status === 200 });
            listTime.add(res.timings.duration);

            sleep(1);

            // 3. Détails d'un livre
            const docId = extractId(res.body);
            if (docId) {
                res = http.get(`${BASE_URL}/get.php?id=${docId}`);
                check(res, { 'Détail OK': (r) => r.status === 200 });
                detailTime.add(res.timings.duration);
            }

            sleep(1);
        });

    } else if (rand < 0.95) {
        // ========== SCÉNARIO CRÉATION (15%) ==========
        group('Création', function () {

            // 1. Affichage formulaire
            let res = http.get(`${BASE_URL}/create.php`);
            check(res, { 'Formulaire OK': (r) => r.status === 200 });

            sleep(2);

            // 2. Soumission du formulaire
            const livre = LIVRES[Math.floor(Math.random() * LIVRES.length)];
            const ts = Date.now();

            res = http.post(`${BASE_URL}/create.php`, {
                title: `${livre.title} - K6 ${ts}`,
                author: livre.author,
                century: livre.century,
                cote: `${livre.cote}-${ts}`,
                langue: livre.langue,
                edition: livre.edition,
            });

            check(res, { 'Création OK': (r) => r.status === 200 || r.status === 302 });
            createTime.add(res.timings.duration);

            sleep(1);
        });

    } else {
        // ========== SCÉNARIO SUPPRESSION (5%) ==========
        group('Suppression', function () {

            // 1. Récupérer un ID depuis la liste
            let res = http.get(`${BASE_URL}/app.php`);
            const docId = extractId(res.body);

            if (docId) {
                sleep(1);

                // 2. Supprimer le document
                res = http.get(`${BASE_URL}/delete.php?id=${docId}`);
                check(res, { 'Suppression OK': (r) => r.status === 200 || r.status === 302 });
                deleteTime.add(res.timings.duration);
            }

            sleep(1);
        });
    }

    // Pause entre les itérations (simule le temps de réflexion)
    sleep(Math.random() * 2 + 1);
}

// ============================================================
// SETUP : Exécuté une fois au début
// ============================================================
export function setup() {
    console.log('🚀 Démarrage du test de charge');
    console.log('📊 Scénarios : 80% lecture | 15% création | 5% suppression');

    // Test de connectivité
    const res = http.get(`${BASE_URL}/app.php`);
    if (res.status !== 200) {
        console.error('❌ Application non accessible !');
    } else {
        console.log('✅ Application accessible');
    }

    return { start: new Date().toISOString() };
}

// ============================================================
// TEARDOWN : Exécuté une fois à la fin
// ============================================================
export function teardown(data) {
    console.log('🏁 Test terminé');
    console.log(`   Début : ${data.start}`);
    console.log(`   Fin   : ${new Date().toISOString()}`);
}