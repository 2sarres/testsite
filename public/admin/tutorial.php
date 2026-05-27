<?php
declare(strict_types=1);
$pageTitle = 'Tutoriel du site';
require_once dirname(__DIR__) . '/_header.php';
require_admin();
db_install($pdo);
?>

<style>
.tutorial-container {
    max-width: 900px;
    margin: 0 auto;
}

.toc {
    background: #f0f8ff;
    border-left: 4px solid var(--gold);
    padding: 20px;
    margin-bottom: 30px;
    border-radius: 6px;
}

.toc h3 {
    margin-top: 0;
    color: #111;
}

.toc ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.toc li {
    margin: 8px 0;
}

.toc a {
    color: var(--gold);
    text-decoration: none;
    font-weight: 500;
}

.toc a:hover {
    text-decoration: underline;
}

.section {
    margin-bottom: 50px;
    padding: 30px;
    background: #fafaf9;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.section h2 {
    color: #111;
    border-bottom: 2px solid var(--gold);
    padding-bottom: 10px;
    margin-top: 0;
}

.section h3 {
    color: #333;
    margin-top: 25px;
}

.instruction-box {
    background: white;
    border-left: 3px solid #3b82f6;
    padding: 15px;
    margin: 15px 0;
    border-radius: 4px;
}

.tip-box {
    background: #fef3c7;
    border-left: 3px solid #f59e0b;
    padding: 15px;
    margin: 15px 0;
    border-radius: 4px;
}

.warning-box {
    background: #fee2e2;
    border-left: 3px solid #ef4444;
    padding: 15px;
    margin: 15px 0;
    border-radius: 4px;
}

.code-box {
    background: #1f2937;
    color: #e5e7eb;
    padding: 15px;
    border-radius: 6px;
    overflow-x: auto;
    margin: 15px 0;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
}

.step-number {
    display: inline-block;
    background: var(--gold);
    color: #111;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    text-align: center;
    line-height: 28px;
    font-weight: bold;
    margin-right: 10px;
}

.feature-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

.feature-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.feature-card h4 {
    margin-top: 0;
    color: #111;
}

@media (max-width: 768px) {
    .feature-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="tutorial-container">
    <div class="card" style="margin-bottom: 30px;">
        <h1 style="margin-top: 0;">📚 Tutoriel Complet — Sky Atlas CMS</h1>
        <p style="font-size: 1.1rem; color: #666; line-height: 1.6;">
            Bienvenue dans le panneau d'administration de Sky Atlas. Ce guide vous explique comment utiliser l'ensemble des fonctionnalités du site.
        </p>
    </div>

    <!-- Table des matières -->
    <div class="toc">
        <h3>📑 Table des matières</h3>
        <ul>
            <li><a href="#dashboard">1. Tableau de bord</a></li>
            <li><a href="#categories">2. Gérer les catégories</a></li>
            <li><a href="#articles">3. Gérer les articles</a></li>
            <li><a href="#images">4. Importer des images</a></li>
            <li><a href="#home">5. Configurer la page d'accueil</a></li>
            <li><a href="#roles">6. Rôles et permissions</a></li>
            <li><a href="#faq">7. Questions fréquentes</a></li>
        </ul>
    </div>

    <!-- 1. Dashboard -->
    <div class="section" id="dashboard">
        <h2>1. Tableau de bord</h2>
        <p>Le tableau de bord est votre point d'entrée pour administrer le site. Vous y trouvez un aperçu rapide des fonctionnalités principales.</p>
        
        <h3>Accès rapide</h3>
        <p>Depuis le tableau de bord, vous pouvez :</p>
        <ul>
            <li><strong>Gérer les catégories</strong> → Les rubriques principales du site</li>
            <li><strong>Créer un nouvel article</strong> → Ajouter du contenu éditorial</li>
            <li><strong>Configurer l'accueil</strong> → Personnaliser le texte et l'image de la page d'accueil</li>
        </ul>

        <div class="instruction-box">
            <strong>✓ Astuce :</strong> Vous êtes actuellement connecté(e) en tant qu'<strong>administrateur</strong>. Vous avez accès à toutes les fonctionnalités.
        </div>
    </div>

    <!-- 2. Catégories -->
    <div class="section" id="categories">
        <h2>2. Gérer les catégories</h2>
        <p>Les catégories organisent les articles en rubriques. Sur la page d'accueil, chaque catégorie s'affiche avec sa propre liste d'articles.</p>

        <h3>Créer une nouvelle catégorie</h3>
        <ol>
            <li>Allez à <strong>Catégories</strong> dans le menu d'admin</li>
            <li>Cliquez sur <strong>+ Nouvelle catégorie</strong></li>
            <li>Remplissez le formulaire</li>
        </ol>

        <div class="feature-grid">
            <div class="feature-card">
                <h4>📋 Champs requis</h4>
                <ul>
                    <li><strong>Libellé :</strong> Nom affiché (ex: "Destinations")</li>
                    <li><strong>Slug :</strong> URL unique (ex: "destinations")</li>
                    <li><strong>Chapô :</strong> Description courte</li>
                </ul>
            </div>
            <div class="feature-card">
                <h4>🖼️ Image décorative</h4>
                <p>Deux options :</p>
                <ul>
                    <li>URL externe (ex: picsum.photos)</li>
                    <li>Upload + recadrage local</li>
                </ul>
            </div>
        </div>

        <h3>À quoi sert le slug ?</h3>
        <p>Le <strong>slug</strong> est l'identifiant unique d'une catégorie. Il est utilisé dans l'URL :</p>
        <div class="code-box">
            Slug: "destinations-europeennes"<br>
            URL: /category.php?slug=destinations-europeennes
        </div>
        <div class="tip-box">
            <strong>💡 Conseil :</strong> Utilisez des tirets (-) pour séparer les mots. Pas d'accents, pas d'espaces. Le système génère automatiquement un slug unique si un conflit survient.
        </div>

        <h3>Gérer l'ordre d'affichage</h3>
        <p>Les catégories s'affichent sur l'accueil dans l'ordre croissant des chiffres <strong>Ordre</strong>. Plus petit = plus haut.</p>
        <div class="instruction-box">
            <strong>Exemple :</strong><br>
            • Catégorie A : Ordre 10 → Affichée 1ère<br>
            • Catégorie B : Ordre 20 → Affichée 2e<br>
            • Catégorie C : Ordre 30 → Affichée 3e
        </div>

        <h3>Modifier une catégorie</h3>
        <ol>
            <li>Cliquez sur <strong>Modifier</strong> à côté de la catégorie</li>
            <li>Changez les informations</li>
            <li>Vous pouvez aussi modifier l'image en uploadant et recadrant une nouvelle</li>
            <li>Cliquez <strong>Enregistrer les modifications</strong></li>
        </ol>

        <h3>Organiser les articles d'une catégorie</h3>
        <p>Pour changer l'ordre des articles dans une catégorie :</p>
        <ol>
            <li>Allez à <strong>Catégories</strong></li>
            <li>Cliquez <strong>Ordre articles</strong> sur la ligne de la catégorie</li>
            <li>Modifiez les numéros d'ordre des articles</li>
            <li>Cliquez <strong>Enregistrer l'ordre</strong></li>
        </ol>

        <div class="warning-box">
            <strong>⚠️ Attention :</strong> Vous ne pouvez supprimer une catégorie que si elle ne contient aucun article. Supprimez ou déplacez d'abord les articles.
        </div>
    </div>

    <!-- 3. Articles -->
    <div class="section" id="articles">
        <h2>3. Gérer les articles</h2>
        <p>Les articles sont le cœur du contenu éditorial. Chaque article est associé à une catégorie et peut avoir une image de couverture.</p>

        <h3>Créer un nouvel article</h3>
        <ol>
            <li>Allez à <strong>Admin</strong> → <strong>+ Nouvel article</strong></li>
            <li>Sélectionnez une <strong>catégorie</strong></li>
            <li>Remplissez le formulaire</li>
        </ol>

        <div class="feature-grid">
            <div class="feature-card">
                <h4>📝 Informations</h4>
                <ul>
                    <li><strong>Titre :</strong> Headline de l'article</li>
                    <li><strong>Slug :</strong> URL unique (auto-généré)</li>
                    <li><strong>Catégorie :</strong> Obligatoire</li>
                    <li><strong>Chapô :</strong> Résumé 1-2 lignes</li>
                </ul>
            </div>
            <div class="feature-card">
                <h4>✍️ Contenu</h4>
                <ul>
                    <li>Éditeur riche WYSIWYG</li>
                    <li>Titres, listes, liens</li>
                    <li>Images embarquées</li>
                    <li>Mise en forme (gras, italique, etc.)</li>
                </ul>
            </div>
        </div>

        <h3>L'éditeur de contenu</h3>
        <p>L'éditeur riche TinyMCE vous permet :</p>
        <ul>
            <li>🔤 Formater le texte (gras, italique, souligné)</li>
            <li>📋 Créer des listes (à puces ou numérotées)</li>
            <li>🔗 Insérer des liens hypertext</li>
            <li>🖼️ Intégrer des images directement dans l'article</li>
            <li>📍 Ajouter des titres et sous-titres (H1, H2, H3)</li>
        </ul>

        <div class="instruction-box">
            <strong>Pour insérer une image dans l'article :</strong>
            <ol>
                <li>Cliquez sur l'icône <strong>Image</strong> dans l'éditeur</li>
                <li>Choisissez l'URL de l'image ou uploadez-la</li>
                <li>L'image est intégrée au contenu HTML</li>
            </ol>
        </div>

        <h3>Image de couverture</h3>
        <p>L'image de couverture s'affiche sur les cartes d'articles (accueil, listes de catégories).</p>
        <ul>
            <li>Format : 800×600 px (ratio 4:3)</li>
            <li>Deux options : URL externe ou upload local avec recadrage</li>
            <li>Le recadrage facilite l'adaptation de n'importe quelle image</li>
        </ul>

        <h3>Publier ou brouillon</h3>
        <p>Cochez <strong>Publier cet article</strong> pour le rendre visible au public. Sinon, il reste en brouillon (visible uniquement par les admins/éditeurs).</p>

        <h3>Modifier un article</h3>
        <ol>
            <li>Depuis l'admin, trouvez l'article</li>
            <li>Cliquez sur <strong>Modifier</strong></li>
            <li>Changez ce que vous voulez</li>
            <li>Cliquez <strong>Enregistrer</strong></li>
        </ol>

        <div class="tip-box">
            <strong>💡 Conseil :</strong> Le chapô est optionnel. S'il est vide, le système affiche les 168 premiers caractères du contenu HTML sur la carte.
        </div>
    </div>

    <!-- 4. Images -->
    <div class="section" id="images">
        <h2>4. Importer des images</h2>
        <p>Deux stratégies pour les images :</p>

        <h3>Option 1 : URL externe</h3>
        <p>Indiquez simplement l'URL d'une image existante sur le web.</p>
        <div class="instruction-box">
            <strong>Exemple :</strong> <code>https://picsum.photos/800/600?random=1</code>
        </div>
        <ul>
            <li>✓ Rapide, pas de stockage</li>
            <li>✓ Facile de changer l'image</li>
            <li>✗ Dépend d'un serveur externe</li>
        </ul>

        <h3>Option 2 : Upload local + recadrage</h3>
        <p>Uploadez une image, recadrez-la à la taille exacte, puis enregistrez-la localement.</p>
        <div class="instruction-box">
            <strong>Processus :</strong>
            <ol>
                <li>Cliquez <strong>Choisir un fichier</strong></li>
                <li>Une modale de recadrage s'ouvre</li>
                <li>Ajustez le cadre au ratio 16:9 (bannières) ou 4:3 (cartes)</li>
                <li>Cliquez <strong>Appliquer le recadrage</strong></li>
                <li>L'image est sauvegardée dans <code>/uploads/</code></li>
            </ol>
        </div>
        <ul>
            <li>✓ Entièrement hébergé localement</li>
            <li>✓ Contrôle total du recadrage</li>
            <li>✗ Consomme de l'espace serveur</li>
        </ul>

        <div class="warning-box">
            <strong>⚠️ Formats acceptés :</strong> JPG, PNG, GIF, WEBP (max 10 Mo)
        </div>
    </div>

    <!-- 5. Home -->
    <div class="section" id="home">
        <h2>5. Configurer la page d'accueil</h2>
        <p>La page d'accueil affiche un hero (en haut) suivi de toutes les catégories avec leurs articles.</p>

        <h3>Personnaliser le hero</h3>
        <ol>
            <li>Allez à <strong>Admin</strong> → <strong>Configurer l'accueil</strong></li>
            <li>Modifiez le <strong>titre principal</strong> (headline)</li>
            <li>Modifiez la <strong>description</strong> (texte sous le titre)</li>
            <li>Changez l'<strong>image de fond</strong> (URL ou upload)</li>
            <li>Cliquez <strong>Enregistrer</strong></li>
        </ol>

        <div class="tip-box">
            <strong>💡 Conseil :</strong> L'image du hero s'affiche en pleine largeur. Utilisez une résolution haute (1200+ px) et un ratio panoramique (16:9).
        </div>

        <h3>Structure de l'accueil</h3>
        <ul>
            <li><strong>Hero :</strong> Titre, description, image (config admin)</li>
            <li><strong>Catégories :</strong> Affichées dans l'ordre défini</li>
            <li><strong>Articles :</strong> Pour chaque catégorie, affichés dans l'ordre défini</li>
        </ul>
    </div>

    <!-- 6. Rôles -->
    <div class="section" id="roles">
        <h2>6. Rôles et permissions</h2>
        <p>Le système différencie deux rôles :</p>

        <div class="feature-grid">
            <div class="feature-card">
                <h4>👑 Administrateur</h4>
                <ul>
                    <li>✓ Accès complet</li>
                    <li>✓ Créer/modifier/supprimer catégories</li>
                    <li>✓ Créer/modifier/supprimer articles</li>
                    <li>✓ Configurer l'accueil</li>
                    <li>✓ Accès au tutoriel admin</li>
                </ul>
            </div>
            <div class="feature-card">
                <h4>✍️ Éditeur</h4>
                <ul>
                    <li>✗ Pas d'accès admin</li>
                    <li>✗ Peut lire le contenu public uniquement</li>
                    <li>⚠️ Le système a une base pour étendre ce rôle</li>
                </ul>
            </div>
        </div>

        <div class="warning-box">
            <strong>⚠️ Note :</strong> Actuellement, seuls les administrateurs ont accès au panneau d'administration. Le rôle "éditeur" est une base pour extension future.
        </div>
    </div>

    <!-- 7. FAQ -->
    <div class="section" id="faq">
        <h2>7. Questions fréquentes</h2>

        <h3>❓ Comment changer mon mot de passe ?</h3>
        <p>Contactez un autre administrateur. Le système ne dispose pas actuellement de fonction d'auto-réinitialisation. Pour ajouter cette fonctionnalité, modifiez <code>public/admin/index.php</code>.</p>

        <h3>❓ Un article ne s'affiche pas sur l'accueil</h3>
        <p>Vérifiez :</p>
        <ul>
            <li>✓ L'article est marqué comme <strong>Publié</strong></li>
            <li>✓ L'article est assigné à une <strong>catégorie</strong></li>
            <li>✓ La catégorie est publiée et visible</li>
        </ul>

        <h3>❓ Comment supprimer une catégorie ?</h3>
        <p>Allez à <strong>Catégories</strong> → <strong>Modifier</strong> → <strong>Zone Dangereuse</strong> → <strong>Supprimer</strong>.</p>
        <p><strong>Attention :</strong> La catégorie doit être vide (aucun article).</p>

        <h3>❓ L'image ne s'affiche pas</h3>
        <p>Vérifiez :</p>
        <ul>
            <li>✓ L'URL est valide (commence par http:// ou https://)</li>
            <li>✓ Le serveur d'images est accessible</li>
            <li>✓ Pour les uploads locaux : le dossier <code>public/uploads/</code> existe et est inscriptible</li>
        </ul>

        <h3>❓ Puis-je prévisualiser les brouillons ?</h3>
        <p>Les brouillons ne sont pas visibles publiquement. Pour voir le contenu avant publication, vous devez être connecté(e) en admin.</p>

        <h3>❓ Comment contacter le support technique ?</h3>
        <p>Le site dispose d'un formulaire de contact public. Les messages sont envoyés par email à l'administrateur (configuré dans <code>src/config.php</code>).</p>

        <h3>❓ Puis-je réinitialiser complètement la base de données ?</h3>
        <p>Supprimez le fichier <code>data.db</code> (dans le dossier racine) et allez sur <code>/install.php</code> pour réinstaller.</p>
        <div class="warning-box">
            <strong>⚠️ Attention :</strong> Cette action efface toutes les données. Sauvegardez avant.
        </div>
    </div>

    <!-- Pied de page tutoriel -->
    <div class="card">
        <h3 style="margin-top: 0;">📞 Support et maintenance</h3>
        <p>Si vous avez des questions techniques ou souhaitez ajouter des fonctionnalités, contactez votre équipe de développement.</p>
        <p style="color: #999; font-size: 0.9rem; margin-bottom: 0;">
            <strong>Dernière mise à jour :</strong> <?= date('d/m/Y à H:i') ?><br>
            <strong>Votre rôle :</strong> Administrateur
        </p>
    </div>
</div>

<?php require dirname(__DIR__) . '/_footer.php'; ?>
