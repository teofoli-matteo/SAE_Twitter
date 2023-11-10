<?php

namespace src\Action;

use src\Db\connexionFactory;
use PDO;

class ProfileAction {
    public function execute(): string {
        $idUser = $_COOKIE['user_id'];
        if (!isset($_COOKIE['user_id'])) {
            return "Veuillez vous connecter pour voir votre profil.";
        }

        try {
            $pdo = connexionFactory::makeConnection();

            // Récupérer les touites de l'utilisateur
            $stmt = $pdo->prepare("SELECT * FROM touite WHERE idUser = :idUser ORDER BY dateTouite DESC");
            $stmt->bindParam(':idUser', $idUser);
            $stmt->execute();

            $myTouites = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Récupérer les touites des personnes que l'utilisateur suit
            $stmt = $pdo->prepare("SELECT t.* FROM touite t
                                    JOIN user_followers uf ON t.idUser = uf.idUser
                                    WHERE uf.followerId = :idUser
                                    ORDER BY dateTouite DESC");
            $stmt->bindParam(':idUser', $idUser);
            $stmt->execute();

            $followedTouites = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Récupérer les touites mentionnant un tag auquel l'utilisateur est abonné
            $stmt = $pdo->prepare("SELECT t.* FROM touite t
                                    JOIN listetouites_tag ltt ON t.idTouite = ltt.idTouite
                                    JOIN user_tag ut ON ltt.idTag = ut.idTag
                                    WHERE ut.idUser = :idUser
                                    ORDER BY dateTouite DESC");
            $stmt->bindParam(':idUser', $idUser);
            $stmt->execute();

            $taggedTouites = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $scoreAction = new CalculerScoreMoyenAction();
            $averageScore = $scoreAction->execute();


            $html = '<h1>Mon profil</h1>';
            $html .= '<a href="index.php?action=followers" class="followers-button">Mes Abonnés</a>';
            $html .= '<a href="index.php?action=tweetForm"><img src="src/Action/toui.png" alt="Touiter"></a>';

            $html .= '<h2>Score moyen</h2>';
            $html .= '<div class="score">';
            $html .= '<p>' . $averageScore . '</p>';
            $html .= '</div>';

            $html .= '<h2>Mes Touites</h2><ul>';
            foreach ($myTouites as $touite) {
                $html .= '<li>';
                $html .= '<strong>' . htmlspecialchars($touite['idUser']) . '</strong>';
                $html .= '<p>' . htmlspecialchars($touite['message']) . '</p>';
                $html .= '<small>' . htmlspecialchars($touite['dateTouite']) . '</small>';
                $html .= '<form class="redbutton" method="post" action="supprimer_touite.php">';
                $html .= '<input type="hidden" name="idTouite" value="' . htmlspecialchars($touite['idTouite']) . '">';
                $html .= '<input type="submit" value="Supprimer">';
                $html .= '</form>';
                $html .= '</li>';
            }
            $html .= '</ul>';

            $html .= '<h2>Touites des personnes que je suis</h2><ul>';
            foreach ($followedTouites as $touite) {
                $html .= '<li>';
                $html .= '<strong>' . htmlspecialchars($touite['idUser']) . '</strong>';
                $html .= '<p>' . htmlspecialchars($touite['message']) . '</p>';
                $html .= '<small>' . htmlspecialchars($touite['dateTouite']) . '</small>';
                $html .= '</li>';
            }
            $html .= '</ul>';

            $html .= '<h2>Touites contenant les tags auxquels je suis abonné</h2><ul>';
            foreach ($taggedTouites as $touite) {
                $html .= '<li>';
                $html .= '<strong>' . htmlspecialchars($touite['idUser']) . '</strong>';
                $html .= '<p>' . htmlspecialchars($touite['message']) . '</p>';
                $html .= '<small>' . htmlspecialchars($touite['dateTouite']) . '</small>';
                $html .= '</li>';
            }
            $html .= '<a href="menu.php" class="back-button">Retour au menu</a>';

            $html .= '</ul>';
            $html .= '<script>
                        // JavaScript pour actualiser la page après la suppression dun touite
                        function refreshPage() {
                        location.reload(true);
                        }
                        </script>';

            return $html;
        } catch (PDOException $e) {
            return "Erreur lors de la récupération des touites: " . $e->getMessage();
        }
    }
}
?>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 5em;
        padding: 0;
        background-color: #f4f4f4;
    }

    h1, h2 {
        color: #333;
        text-align: center;
    }

    h1 {
        margin-top: 5px; /* Ajoute une marge en haut pour l'espace */
        position: relative; /* Position relative pour permettre des éléments enfants positionnés par rapport à ce titre */
    }

    h1::before,
    h1::after {
        content: ''; /* Contenu vide pour générer les éléments décoratifs */
        position: absolute; /* Position absolue par rapport au titre h2 */
        top: 50%; /* Aligner au milieu du titre */
        width: 60%; /* Largeur des éléments décoratifs (peut être ajustée) */
        height: 2px; /* Hauteur des éléments décoratifs (peut être ajustée) */
        background-color: #333; /* Couleur des éléments décoratifs */
    }

    h1::before {
        left: 0; /* Positionner à gauche du titre */
        margin-left: -25%; /* Ajustement pour que le titre ne dépasse pas les bords */
    }

    h1::after {
        right: 0; /* Positionner à droite du titre */
        margin-right: -25%; /* Ajustement pour que le titre ne dépasse pas les bords */
    }



    ul {
        list-style: none;
        padding: 0;
    }

    li {
        background-color: #fff;
        margin-bottom: 10px;
        padding: 20px;
        border-radius: 3px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    li strong {
        display: block;
        color: #666;
    }

    li p {
        margin: 10px 0;
        color: #333;
    }

    li small {
        display: block;
        text-align: right;
        color: #999;
    }

    .back-button {
        display: inline-block;
        margin-top: 20px;
        padding: 10px 20px;
        background-color: #333;
        color: #fff;
        text-decoration: none;
        border-radius: 3px;
        transition: background-color 0.3s ease;
    }

    .back-button:hover {
        background-color: #666;
    }

    .score {
        background-color: #fff;
        margin-bottom: 10px;
        padding: 20px;
        border-radius: 3px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .followers-button {
        display: inline-block;
        margin-top: 20px;
        padding: 10px 20px;
        background-color: #333;
        color: #fff;
        text-decoration: none;
        border-radius: 3px;
        transition: background-color 0.3s ease;
    }

    .followers-button:hover {
        background-color: #666;
    }

    img {
        float: right;
        max-width: 50px;
        max-height: 50px;
    }


</style>

