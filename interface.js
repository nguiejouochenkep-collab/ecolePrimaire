// ========== FONCTIONS GLOBALES (accessibles depuis les éléments HTML) ==========

// Charge la page de promotion via fetch pour mettre à jour uniquement #dynamic-content
// Évite la navigation complète et préserve la sidebar + navbar
function chargerPromotion(id_classe) {
    var url = 'index.php?action=promotion';
    if (id_classe && id_classe !== '') url += '&id_classe=' + encodeURIComponent(id_classe);

    var dynamic = document.getElementById('dynamic-content');
    if (!dynamic) {
        // fallback : navigation complète si le conteneur n'existe pas
        window.location.href = url;
        return;
    }

    dynamic.innerHTML = `
        <div class="text-center p-5">
            <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
            <p class="mt-3 text-secondary font-weight-bold">Chargement...</p>
        </div>`;

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (resp) {
            if (!resp.ok) throw new Error('Erreur ' + resp.status);
            return resp.text();
        })
        .then(function (html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            var nouveau = doc.getElementById('dynamic-content') || doc.getElementById('content-to-load') || doc.body;
            if (nouveau) {
                dynamic.innerHTML = nouveau.innerHTML;
                // Ré-exécuter les scripts inclus dans le contenu
                Array.from(nouveau.querySelectorAll('script')).forEach(function(s) {
                    var ns = document.createElement('script');
                    if (s.src) ns.src = s.src; else ns.text = s.textContent || s.innerText || '';
                    document.body.appendChild(ns);
                });
            } else {
                dynamic.innerHTML = html;
            }
            try { history.pushState({}, '', url); } catch (e) {}
        })
        .catch(function (err) {
            dynamic.innerHTML = `<div class="alert alert-danger">Erreur lors du chargement : ${err.message}</div>`;
        });
}

// ========== FIN FONCTIONS GLOBALES ==========

document.addEventListener("DOMContentLoaded", function () {
    
    const sidebar = document.getElementById("sidebar");
    const mainContent = document.getElementById("main-content");
    const toggleBtn = document.getElementById("toggle-sidebar-btn");
    const closeBtn = document.getElementById("close-sidebar-btn");
    const dynamicContent = document.getElementById("dynamic-content");

    // Variable globale pour stocker l'instance du graphique et éviter les superpositions au rechargement
    let monGraphique = null;

    // 1. Animation et repli de la barre latérale (Sidebar)
    if (toggleBtn && sidebar && mainContent) {
        toggleBtn.addEventListener("click", function () {
            sidebar.classList.toggle("collapsed");
            sidebar.classList.toggle("active");
            mainContent.classList.toggle("expanded");
        });
    }

    if (closeBtn && sidebar && mainContent) {
        closeBtn.addEventListener("click", function () {
            sidebar.classList.remove("active");
            sidebar.classList.add("collapsed");
            mainContent.classList.add("expanded");
        });
    }

    // 2. CHARGEMENT DYNAMIQUE DES PAGES (AJAX via Fetch API)
    // Utilise délégation d'événements pour intercepter tous les liens internes
    function gererChargementDynamique() {
        // utilitaire : détecte si le lien est interne et doit être chargé dans le conteneur
        function estLienInterne(a) {
            if (!a || !a.href) return false;
            // Ne pas intercepter les liens de déconnexion, mailto, tel, download ou target externes
            if (a.href.indexOf('deconnexion.php') !== -1) return false;
            if (a.protocol === 'mailto:' || a.protocol === 'tel:') return false;
            if (a.hasAttribute('download') || a.target === '_blank') return false;
            // même origine
            try {
                const urlObj = new URL(a.href, location.href);
                return urlObj.origin === location.origin;
            } catch (e) {
                return false;
            }
        }

        async function chargerUrl(url, pushHistory = true, clickedLink = null) {
            if (!dynamicContent) return;

            dynamicContent.innerHTML = `
                <div class="text-center p-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                    <p class="mt-3 text-secondary font-weight-bold">Chargement en cours...</p>
                </div>
            `;

            try {
                const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error('Erreur de chargement: ' + response.status);
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                // Priorité : l'élément exact `dynamic-content` si présent, sinon `content-to-load`, sinon body
                const nouveauContenu = doc.getElementById('dynamic-content') || doc.getElementById('content-to-load') || doc.body;

                let scriptsToExecute = [];
                if (nouveauContenu) {
                    dynamicContent.innerHTML = nouveauContenu.innerHTML;
                    scriptsToExecute = Array.from(nouveauContenu.querySelectorAll('script'));
                } else {
                    dynamicContent.innerHTML = html;
                    scriptsToExecute = Array.from(dynamicContent.querySelectorAll('script'));
                }

                // Marquer les formulaires insérés pour utilisation AJAX
                dynamicContent.querySelectorAll('form').forEach(f => f.setAttribute('data-ajax', 'true'));

                // Met à jour la classe active si le lien cliqué est dans la sidebar
                if (clickedLink && clickedLink.classList.contains('nav-link')) {
                    document.querySelectorAll('.sidebar .nav-link').forEach(l => l.classList.remove('active'));
                    clickedLink.classList.add('active');
                }

                // Ré-exécution des scripts du contenu chargé dynamiquement
                scriptsToExecute.forEach(vieuxScript => {
                    const nouveauScript = document.createElement('script');
                    if (vieuxScript.src) {
                        nouveauScript.src = vieuxScript.src;
                    } else {
                        nouveauScript.text = vieuxScript.textContent || vieuxScript.innerText || '';
                    }
                    document.body.appendChild(nouveauScript);
                    // On ne retire pas immédiatement le script afin d'éviter certains comportements asynchrones
                });

                // Met à jour l'URL dans l'historique s'il faut
                if (pushHistory) {
                    try {
                        history.pushState({ url: url }, '', url);
                    } catch (e) {
                        console.warn('pushState failed', e);
                    }
                }

            } catch (error) {
                console.error('Erreur AJAX :', error);
                dynamicContent.innerHTML = `
                    <div class="alert alert-danger m-3" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Erreur lors du chargement de la page.
                    </div>
                `;
            }
        }

        // délégation : capture tous les clics et filtre les <a> à intercepter
        // Utilisation de la phase de capture pour intercepter avant d'autres handlers
        document.addEventListener('click', function (e) {
            const a = e.target.closest('a');
            if (!a) return;
            if (!estLienInterne(a)) return;

            // Eviter d'intercepter si l'utilisateur veut ouvrir en nouvel onglet avec Ctrl/Cmd
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

            const url = a.getAttribute('href');
            if (!url || url === '#' || url.trim() === '') return;

            e.preventDefault();
            chargerUrl(url, true, a);
        });

        // Interception des formulaires pour envoi AJAX (évite navigation complète)
        const ajaxFormHandler = function (e) {
            const form = e.target;
            if (!form || !(form instanceof HTMLFormElement)) return;

            // N'intercepte que les formulaires marqués data-ajax ou ceux que l'on charge dynamiquement
            if (!form.hasAttribute('data-ajax')) return;

            e.preventDefault();

            // Soumission via fetch
            (async function submitFormAjax() {
                const action = form.getAttribute('action') || location.href;
                const method = (form.getAttribute('method') || 'GET').toUpperCase();

                try {
                    const fd = new FormData(form);

                    let fetchOptions = {
                        method: method,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    };

                    if (method === 'GET') {
                        // Concatène les params à l'URL
                        const params = new URLSearchParams(fd);
                        const urlWithParams = action + (action.indexOf('?') === -1 ? '?' : '&') + params.toString();
                        const resp = await fetch(urlWithParams, { headers: fetchOptions.headers });
                        if (!resp.ok) throw new Error('Erreur de soumission: ' + resp.status);
                        const html = await resp.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const nouveauContenu = doc.getElementById('dynamic-content') || doc.getElementById('content-to-load') || doc.body;
                        dynamicContent.innerHTML = nouveauContenu ? nouveauContenu.innerHTML : html;
                    } else {
                        fetchOptions.body = fd;
                        const resp = await fetch(action, fetchOptions);
                        if (!resp.ok) throw new Error('Erreur de soumission: ' + resp.status);
                        const html = await resp.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const nouveauContenu = doc.getElementById('dynamic-content') || doc.getElementById('content-to-load') || doc.body;
                        dynamicContent.innerHTML = nouveauContenu ? nouveauContenu.innerHTML : html;
                    }

                    // Ré-exécuter scripts éventuels
                    const scripts = Array.from(dynamicContent.querySelectorAll('script'));
                    scripts.forEach(s => {
                        const ns = document.createElement('script');
                        if (s.src) ns.src = s.src;
                        else ns.text = s.textContent || s.innerText || '';
                        document.body.appendChild(ns);
                    });

                    // Mettre à jour l'URL si le formulaire soumet vers une URL différente
                    try { history.pushState({ url: action }, '', action); } catch (e) { /* ignore */ }

                } catch (err) {
                    console.error('Erreur lors de la soumission AJAX du formulaire', err);
                    const alertBox = `<div class="alert alert-danger">Erreur lors de l'enregistrement : ${err.message}</div>`;
                    if (dynamicContent) dynamicContent.insertAdjacentHTML('afterbegin', alertBox);
                }
            })();
        };

        if (dynamicContent) {
            dynamicContent.addEventListener('submit', ajaxFormHandler, true);
        }
        document.addEventListener('submit', ajaxFormHandler, true);

        // Gestion du back/forward
        window.addEventListener('popstate', function (event) {
            const url = location.href;
            // charge sans pousser dans l'historique
            chargerUrl(url, false, null);
        });
    }

    // Fonction utilitaire pour afficher l'erreur directement sur l'interface de l'application
    function afficherErreurInterface(message) {
        const errorContainer = document.getElementById("js-error-display");
        if (errorContainer) {
            errorContainer.innerHTML = `
                <div class="alert alert-danger shadow-sm mb-4" role="alert">
                    <h5 class="alert-heading font-weight-bold"><i class="fas fa-exclamation-circle mr-2"></i>Erreur de chargement des données</h5>
                    <p class="mb-0">Détails techniques : <code>${message}</code></p>
                    <small class="text-muted">Vérifie tes tables SQL (eleve, classe, enseignant, paiement) ou la configuration de ta base de données.</small>
                </div>
            `;
            errorContainer.classList.remove("d-none");
        }
    }

    // Nouvelle fonction pour dessiner le graphique
    function initialiserGraphique(donneesGraphique) {
        const conteneurGraphique = document.getElementById("liste-repartition-classes");
        
        if (!conteneurGraphique) return;

        // On remplace proprement le texte de chargement par un élément Canvas pour Chart.js
        conteneurGraphique.innerHTML = '<canvas id="chartEffectifs" style="max-height: 280px;"></canvas>';

        const labelsClasses = donneesGraphique.map(item => item.nom_classe || item.classe);
        const effectifsClasses = donneesGraphique.map(item => item.effectif || item.total);

        const ctx = document.getElementById('chartEffectifs').getContext('2d');
        
        if (monGraphique) {
            monGraphique.destroy();
        }

        // Création du graphique en barres
        monGraphique = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labelsClasses,
                datasets: [{
                    label: "Nombre d'élèves",
                    data: effectifsClasses,
                    backgroundColor: 'rgba(52, 152, 219, 0.8)', // Bleu assorti à votre thème EduManage
                    borderColor: 'rgba(44, 62, 80, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // 3. Récupération des compteurs
    function chargerStatistiques() {
        // On cible d'abord l'action de notre contrôleur centralisé index.php
        fetch("index.php?action=dashboard_stats")
            .then(response => {
                if (!response.ok) throw new Error("Le serveur a répondu avec un statut " + response.status);
                return response.text(); // On récupère d'abord en texte pour inspecter d'éventuelles erreurs PHP
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data && (data.status === "success" || data.total_eleves !== undefined)) {
                        // Harmonisation de la structure qu'elle vienne de data.stats ou de data directement
                        const stats = data.stats ? data.stats : data;
                        
                        if(document.getElementById("count-eleves")) document.getElementById("count-eleves").textContent = stats.total_eleves ?? "0";
                        if(document.getElementById("count-classes")) document.getElementById("count-classes").textContent = stats.total_classes ?? "0";
                        if(document.getElementById("count-enseignants")) document.getElementById("count-enseignants").textContent = stats.total_enseignants ?? "0";
                        if(document.getElementById("sum-scolarite")) document.getElementById("sum-scolarite").textContent = Number(stats.total_recettes ?? 0).toLocaleString('fr-FR');
                        
                        // Génération du graphique si les données sont présentes
                        if (data.graphique) {
                            initialiserGraphique(data.graphique);
                        }
                    } else {
                        throw new Error("Structure de données JSON invalide reçue du serveur.");
                    }
                } catch (err) {
                    console.error("Le serveur n'a pas renvoyé un JSON valide. Reçu :", text);
                    afficherErreurInterface("Format JSON invalide. Le serveur a renvoyé une erreur ou du texte brut.");
                    // En cas d'erreur de parsing, on appelle le secours sur un script autonome pour voir si ça répond mieux
                    secoursInterfaceDirecte(text);
                }
            })
            .catch(error => {
                console.error("Erreur de connexion :", error);
                afficherErreurInterface(error.message);
                secoursInterfaceDirecte(error.message);
            });
    }

    // Fonction de secours modifiée pour cibler un fichier de secours direct ou appliquer des valeurs par défaut sans boucler
    function secoursInterfaceDirecte(erreurPrecedente) {
        // On évite de ré-interroger la même route brisée index.php?action=dashboard_stats
        // Si tu as un fichier API direct (ex: api/stats.php), mets son URL ici, sinon on gère les valeurs par défaut
        fetch("api_dashboard_secours.php")
            .then(res => {
                if (!res.ok) throw new Error("Secours inaccessible");
                return res.json();
            })
            .then(data => {
                const stats = data.stats ? data.stats : data;
                if(document.getElementById("count-eleves")) document.getElementById("count-eleves").textContent = stats.total_eleves ?? "0";
                if(document.getElementById("count-classes")) document.getElementById("count-classes").textContent = stats.total_classes ?? "0";
                if(document.getElementById("count-enseignants")) document.getElementById("count-enseignants").textContent = stats.total_enseignants ?? "0";
                if(document.getElementById("sum-scolarite")) document.getElementById("sum-scolarite").textContent = Number(stats.total_recettes ?? 0).toLocaleString('fr-FR');
                
                if (data.graphique) {
                    initialiserGraphique(data.graphique);
                }
            })
            .catch(e => {
                console.error("Le mode secours n'a pas pu s'exécuter :", e);
                // Si l'élément de chargement graphique est resté bloqué, on affiche l'explication
                const conteneurGraphique = document.getElementById("liste-repartition-classes");
                if (conteneurGraphique && conteneurGraphique.innerHTML.includes("Calcul des effectifs")) {
                    conteneurGraphique.innerHTML = `
                        <p class="text-center text-danger m-0 p-3">
                            <i class="fas fa-exclamation-triangle mr-2"></i> Erreur SQL ou de structure de base de données.
                        </p>
                    `;
                }
            });
    }

    // Lancement
    chargerStatistiques();
    gererChargementDynamique();
});