<?php 
//AddEvent.php
include("./model/event.php");

session_start();


$statut = isset($_SESSION["statut"]) ? $_SESSION["statut"] : "draft";



   $list = [
    "France" => [1,3,4,5,9],
    "UK" => [11,13,14,15,19],
    "Italy" => [54,55],
    "Spain" => [6,47,49]
];

    if (!isset($_SESSION["reponses"])) {
        $_SESSION["response"] = [];
    }

    if (isset($_GET['new'])) {
    $_SESSION['reponses'] = [];
    $_SESSION['errors'] = [];
    $_SESSION['statut'] = 'draft';
    $_SESSION['event_id'] = null;
    $_SESSION['images'] = [];
    header("location: index.php?page=AddEvent");
    exit;
}

//var_dump(getListEvent());

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add an Event</title>
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>

<div class="page">
 
    <div class="entete">
        <h1  style = "Color : red" >Add an Event</h1>
        <div style = "font-size : 20px">
            Event Status : 
            <span class="badge badge-<?= $statut ?>"><?= $statut ?></span>
        </div>
    </div>

    <form method="POST" action="./validation/validAddEvent.php" enctype="multipart/form-data">

        <input type="hidden" name="statut_actuel" value="<?= $statut ?>">

        <h2>Event information :</h2>

        <div class="ligne">
            <label class = "<?= isset($_SESSION["errors"]["nom_projet"]) ? "error" : "";  ?>"   >Project name :</label >
            <input  class = "<?= isset($_SESSION["errors"]["nom_projet"]) ? "error" : "";  ?>" type="text" name="nom_projet" value = "<?=
            isset($_SESSION["reponses"]["nom_projet"]) ? $_SESSION["reponses"]["nom_projet"] : ""; ?>">
        
        </div>

        <div class="ligne">
            <label>Event type :</label>
            <select name="type_event">
                <option>Fun Month</option>
                <option>Donnation</option>
            </select>
        </div>
    
        <div class="ligne">
            <label>Event link :</label>
            <input type="text" name="link" style="width:300px" value = "<?=
            isset($_SESSION["reponses"]["link"]) ? $_SESSION["reponses"]["link"] : "event.misgroup.io/....."; ?>">
        </div>

        <div class="ligne">
            <label class = "<?= isset($_SESSION["errors"]["launching_date"]) ? "error" : "";  ?>" >Event launch :</label>
            <input class = "<?= isset($_SESSION["errors"]["launching_date"]) ? "error" : "";  ?>" type="date" name="launching_date" value = "<?=
            isset($_SESSION["reponses"]["launching_date"]) ? $_SESSION["reponses"]["launching_date"] : ""; ?>">
            <?php if (isset($_SESSION["errors"]["launching_date"]) ) { ?>
            <span class = "error"> <?= $_SESSION["errors"]["launching_date"] ?>  </span>  
             <?php   } ?>
        </div>
        
        <div class="ligne">
            <label class = "<?= isset($_SESSION["errors"]["result_date"]) ? "error" : "";  ?>" >Display results :</label>
            <input class = "<?= isset($_SESSION["errors"]["result_date"]) ? "error" : "";  ?>" type="date" name="result_date" value = "<?=
            isset($_SESSION["reponses"]["result_date"]) ? $_SESSION["reponses"]["result_date"] : ""; ?>">
            <?php if (isset($_SESSION["errors"]["result_date"]) ) { ?>
            <span class = "error"> <?= $_SESSION["errors"]["result_date"] ?>  </span>  
             <?php   } ?>
            
        </div>

        <div class="ligne">
            <label class = "<?= isset($_SESSION["errors"]["end_date"]) ? "error" : "";  ?>" >Event end :</label>
            <input class = "<?= isset($_SESSION["errors"]["end_date"]) ? "error" : "";  ?>" type="date" name="end_date" value = "<?=
            isset($_SESSION["reponses"]["end_date"]) ? $_SESSION["reponses"]["end_date"] : ""; ?>">
            <?php if (isset($_SESSION["errors"]["end_date"]) ) { ?>
            <span class = "error"> <?= $_SESSION["errors"]["end_date"] ?>  </span>  
             <?php   } ?>
        </div>
 

        <div class="ligne">
            <label class = "<?= isset($_SESSION["errors"]["pays_list[]"]) ? "error" : "";  ?>" >Country :</label>
            <div class="pays-liste" >
                <label><input type="checkbox" value="france" name="pays_list[]" onchange="updateOnglets()"
                    <?= isset($_SESSION["reponses"]["pays_list"]) && in_array("france", $_SESSION["reponses"]["pays_list"]) ? "checked" : "" ?>>
                    France</label>

                <label><input type="checkbox" value="uk" name="pays_list[]" onchange="updateOnglets()"
                    <?= isset($_SESSION["reponses"]["pays_list"]) && in_array("uk", $_SESSION["reponses"]["pays_list"]) ? "checked" : "" ?>>
                    UK</label>

                <label><input type="checkbox" value="italy" name="pays_list[]" onchange="updateOnglets()"
                    <?= isset($_SESSION["reponses"]["pays_list"]) && in_array("italy", $_SESSION["reponses"]["pays_list"]) ? "checked" : "" ?>>
                    Italy</label>

                <label><input type="checkbox" value="spain" name="pays_list[]" onchange="updateOnglets()"
                    <?= isset($_SESSION["reponses"]["pays_list"]) && in_array("spain", $_SESSION["reponses"]["pays_list"]) ? "checked" : "" ?>>
                    Spain</label>
            </div>
        </div>


         <?php if (isset($_SESSION["errors"]["pays_list[]"])): ?>
            <span class="error"><?= $_SESSION["errors"]["pays_list[]"] ?></span>
        <?php endif; ?>




        <div class="ligne">
            <label></label>
            <label><input type="checkbox"> Enable an internal version of the event </label>
        </div>


        <?php if (!empty($_SESSION["errors"])){ ?>

          
        <span class = "error" > some input(s) are empty </span>  

        <?php } ?>

        <br>
        <hr>

        <div class="bloc-banniere">

            <h2 class = "text-color : grey">Event banner management</h2>

            <p style="color:#999; font-style:italic;"> Count equals the number of missing images per language </p>



            <div class="onglets" id="onglets">
                <button type="button" class="onglet" id="onglet-france" style="display:none" onclick="chargeOnglet('france', this)">
                    French (<span id="compteur-france">20</span>)
                </button>
                <button type="button" class="onglet" id="onglet-uk"     style="display:none" onclick="chargeOnglet('uk', this)">
                    English (<span id="compteur-uk">14</span>)
                </button>
                <button type="button" class="onglet" id="onglet-italy"  style="display:none" onclick="chargeOnglet('italy', this)">
                    Italian (<span id="compteur-italy">0</span>)
                </button>
                <button type="button" class="onglet" id="onglet-spain" style="display:none" onclick="chargeOnglet('spain', this)">
                    Spanich (<span id="compteur-spain">6</span>)
                </button>
            </div>


            <p id="message-aucun-pays" style="color:#999; font-style:italic;">
                Please select at least one country
            </p>


            <div class="contenu" id="france">

                <h3>Main display</h3>
                <div class="grille">
                    <div class="slot">
                        <div class="slot-label" >FCT 1000x90 (Co et Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "1000x90" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">AP 455X184 (Co et Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "455X184"><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">TDP 500X400 (Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "500X400"><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">DCM 428X125 (Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "428X125" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">P 220x181 (Co et Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "220x181"><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">FCT & TRY 1000x90 (Co et Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "1000x90"><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">DCM 298x130 (CO) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "298x130"><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">RS 455X184 (Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "455X184" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">RS 455X184 (CO) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "455X184"><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">APPS 620X180 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "620X180" ><span>Add PNG picture</span></div>
                    </div>
                </div>

                <h3>Affichage des résultats</h3>
                <div class="grille">
                    <div class="slot">
                        <div class="slot-label">FCT 1000x90 (Co et Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "1000x90" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">AP 455x184 (Co et Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "455x184" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">TDP 500x400 (Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "500x400" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">DCM 428x125 (Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "428x125" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">P 220x181 (Co et Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "220x181" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">DCM 298x130 (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "298x130" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">FCT & TRY 1000x90 (Co et Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "1000x90" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">RS 455x184 (Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "455x184" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">RS 455x184 (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "455x184" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">APPS 620x180 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "620x180"><span>Add PNG picture</span></div>
                    </div>
                </div>
            </div>


            <div class="contenu" id="uk">

                <h3>Affichage principal</h3>
                <div class="grille">
                    <div class="slot">
                        <div class="slot-label">Survey Friend 870x310 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "870x310" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">Panel Opinion 455x184 (Co et Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "455x184" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">Paidproduct testing 500x400 (Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "500x400"><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">Mystery shopper 428x125 (Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "428x125" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">P 220x181 (Co et Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "220x181" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">APPS 620x180 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "620x180" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">PFG (Taille à def) (Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "" ><span>Add PNG picture</span></div>
                    </div>
                </div>

                <h3>Affichage des résultats</h3>
                <div class="grille">
                    <div class="slot">
                        <div class="slot-label">Survey Friend 870x310 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "870x310" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">Panel Opinion 455x184 (Co et Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "455x184"><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">Paidproduct testing 500x400 (Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "500x400" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">Mystery shopper 428x125 (Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "428x125" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">P 220x181 (Co et Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "220x181" ><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">APPS 620x180 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "620x180"><span>Add PNG picture</span></div>
                    </div>
                    <div class="slot">
                        <div class="slot-label">PFG (Taille à def) (Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size = "" ><span>Add PNG picture</span></div>
                    </div>
                </div>
            </div>


            <div class="contenu" id="italy"></div>






            <div class="contenu" id="spain">

            <h3>Affichage principal</h3>
            <div class="grille">
                <div class="slot">
                    <div class="slot-label">FCT & TRY 1000x90 (Co et Déco) <span class="icon-interrogation">?</span></div>
                    <div class="slot-input" data-size="1000x90"><span>Add PNG picture</span></div>
                </div>
                <div class="slot">
                    <div class="slot-label">RS 455x184 (Deco) <span class="icon-interrogation">?</span></div>
                    <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div>
                </div>
                <div class="slot">
                    <div class="slot-label">RS 455x184 (Co) <span class="icon-interrogation">?</span></div>
                    <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div>
                </div>
            </div>

            <h3>Affichage des résultats</h3>
            <div class="grille">
                <div class="slot">
                    <div class="slot-label">FCT & TRY 1000x90 (Co et Déco) <span class="icon-interrogation">?</span></div>
                    <div class="slot-input" data-size="1000x90"><span>Add PNG picture</span></div>
                </div>
                <div class="slot">
                    <div class="slot-label">RS 455x184 (Deco) <span class="icon-interrogation">?</span></div>
                    <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div>
                </div>
                <div class="slot">
                    <div class="slot-label">RS 455x184 (Co) <span class="icon-interrogation">?</span></div>
                    <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div>
                </div>
            </div>
        </div>



        </div>

        <div class="boutons">
            <button type="submit" name="action" value="save" class="btn-save"
                <?= $statut == 'prod' ? 'disabled' : '' ?>>
                Save
            </button>

            <button type="submit" name="action" value="pre-publish" class="btn-publish"
                <?= $statut != 'draft' ? 'disabled' : '' ?>>
                <?= $statut === 'pre-prod' ? 'Publish' : 'Pre-publish' ?>
            </button>

            <a href="./reset.php" style="font-size:11px; color:#999;">Reset session</a> 

        </div>

    </form>
</div>
<script>
    var savedImages = <?= json_encode($_SESSION['images'] ?? []) ?>;
</script>
<script src="./js/script.js"></script>
</body>
</html>