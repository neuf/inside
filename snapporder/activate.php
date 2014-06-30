<?php
/* User activation form (step 2).
 * SnappOrder first saves initial data in our temp user db
 *
 * TODO possible to activate expired memberships?
 */
set_include_path("../includes/");

require_once("../inside/credentials.php");
require_once("../inside/functions.php");
require_once("../includes/DB.php");

require_once("lib/functions.php");
require_once("config.php");

if( !isset($_GET['userid']) || !isset($_GET['token']) ) {
    echo "Ugyldig eller utgått aktiveringslenke. Ta kontakt med medlemskap@studentersamfundet.no hvis du har spørsmål.";
    set_response_code(400);
    die();
}

/* Connect to database */
$options = array( 'debug' => 2, 'portability' => DB_PORTABILITY_ALL );
$conn = DB::connect(getDSN(), $options);
if(DB :: isError($conn)) {
    echo $conn->toString();
    set_response_code(500);
    echo 'Could not connect to DB';
    die();
}
$conn->setFetchMode(DB_FETCHMODE_ASSOC);

/* Fetch user and check for valid token */
$user = get_user($_GET['userid']);
if(!$user) {
    echo "Ugyldig eller utgått aktiveringslenke. Ta kontakt med medlemskap@studentersamfundet.no hvis du har spørsmål.";
    set_response_code(400);
    die();
}
if( !check_token($user, $_GET['token'], SECRET_KEY)) {
    echo "Ugyldig eller utgått aktiveringslenke. Ta kontakt med medlemskap@studentersamfundet.no hvis du har spørsmål.";
    set_response_code(400);
    die();
}

// empty default form values
$validation_errors = "";
$username = "";
$password = "";
$newsletter_checked = " checked";

if( isset($_POST['submit']) ) {
    try {
        $data = validate_activation_form($_POST); // can throw

        // persist
        save_activation_form($data);
        // redirect to confirmation page
        redirect("/snapporder/activate_confirmed.php");
        die();

    } catch(ValidationException $e) {
        $validation_errors = $e->getMessage();

        /* Refill form values*/
        $username = $_POST['username'];
        $password = $_POST['password'];
        $newsletter_checked = isset($_POST['newsletter']) ? " checked" : "";
    }
}

?>
<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width" />
    <title>Aktiver medlemskapet ditt hos Det Norske Studentersamfund</title>
    <link href='http://fonts.googleapis.com/css?family=Arvo:700' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" type="text/css" media="all" href="css/style.css" />

    <!--<script src="js/jquery-1.11.0.js"></script>
    <script src="bower_components/foundation/js/foundation.js"></script>
    <script src="js/app.js"></script>-->

    <!-- GA? -->

    <!--[if lt IE 9]>
        <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
</head>

<body>
<div class="container">
    <h1 class="title">Aktiver medlemskapet</h1>
    <em class="subtitle">på Det Norske Studentersamfund</em>
    <header class="about">
        <p>Hei <strong><?php echo $user['firstname']." ".$user['lastname']; ?></strong>, du er veldig nære å kunne:</p>
        <ul class="incentives">
            <li>Få tilgang til medlemskontoen din</li>
            <li>Bruke trådløsnett på Chateau Neuf</li>
            <li>Bli aktiv på Studentersamfundet</li>
        </ul>
        <p class="imperative">Fullfør profilen din ved å angi <em>brukernavn</em> og <em>passord</em>.</p>
        <h2>Din profil</h2>
        <ul class="profile">
            <li><strong>Navn:</strong><span class="profile-value"><?php echo $user['firstname']. " " .$user['lastname']; ?></span></li>
            <li><strong>E-post:</strong><span class="profile-value email-value"><?php echo $user['email']; ?></span></li>
            <li><strong>Medlemsnummer:</strong><span class="profile-value"><?php echo $user['memberid']; ?></span></li>
        </ul>
    </header>
    <section class="activation">
        <?php if(strlen($validation_errors) > 0 ) {
            echo "<span class=\"error\">$validation_errors</span>";
        } ?>
        <form method="post" class="activation-form">
            <!-- Profile -->
            <input type="hidden" name="userid" value="<?php echo $user['memberid']; ?>"/>
            <input type="hidden" name="email" value="<?php echo $user['email']; ?>"/>
            <input type="hidden" name="firstname" value="<?php echo $user['firstname']; ?>"/>
            <input type="hidden" name="lastname" value="<?php echo $user['lastname']; ?>"/>
            <!-- Account -->
            <div class="form-row">
                <label for="id_username">Brukernavn:</label><input id="id_username" type="text" name="username" placeholder="Brukernavn" value="<?php echo $username; ?>" />
            </div>
            <div class="form-row">
                <label for="id_password">Passord:</label><input id="id_password" type="password" name="password" placeholder="Passord" value="<?php echo $password; ?>"/>
            </div>
            <div class="form-row">
                <label for="id_place_of_study">Studiested:</label><?php institutions(); ?>
            </div>
            <label for="id_newsletter">Nyhetsbrev:</label><div class="newsletter-text"><input type="checkbox" id="id_newsletter" name="newsletter" value="1"<?php echo $newsletter_checked; ?> /><label for="id_newsletter" class="no-width">Nyheter og arrangementer</label></div>
            <button type="submit" name="submit" class="btn-submit">Aktiver medlemskapet mitt</button>
        </form>
    </section>
    <footer>
        <a href="https://studentersamfundet.no" title="Det Norske Studentersamfund"><img src="/snapporder/img/logo.png" width="100" height="100" class="logo"></a>
        <img src="/snapporder/img/snapporder_logo_dark_liggende.png">
    </footer>
</div>
</body>
</html>
