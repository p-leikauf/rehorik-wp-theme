<?php
/**
 * Ticket-PDF Template
 *
 * Available via $reh_tpl array (set by class-reh-ticket-pdf.php):
 *   $reh_tpl['ticket_name']   string  Name/Titel des Events
 *   $reh_tpl['ticket_id']     string  Ticketnummer (order_id-variation_id)
 *   $reh_tpl['organizer']     string  Veranstalter
 *   $reh_tpl['date']          string  Formatiertes Datum + Uhrzeit
 *   $reh_tpl['location']      string  Ort + Adresse
 *   $reh_tpl['holder_name']   string  Name des Käufers
 *   $reh_tpl['assets_dir']    string  Absoluter Dateipfad zum assets/-Ordner des Plugins
 *   $reh_tpl['assets_url']    string  URL zum assets/-Ordner des Plugins
 *
 * Asset-Platzhalter:
 *   - Logo:         assets/img/logos/logo-391px.png
 *   - Maskottchen:  assets/img/hugo/hugo-365px.png
 *   - Footer-Bild:  assets/img/footer/footer-ticket-pdf-2480px.png
 *   - Schriften:    assets/fonts/cond.ttf + cond-bold.ttf
 *
 * @package rehorik-events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// dompdf loads images via local absolute file paths.
$img_logo   = $reh_tpl['assets_dir'] . '/img/logos/logo-391px.png';
$img_hugo   = $reh_tpl['assets_dir'] . '/img/hugo/hugo-365px.png';
$img_footer = $reh_tpl['assets_dir'] . '/img/footer/footer-ticket-pdf-2480px.png';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <style>
        html, body {
            font-family: 'Cond', serif;
            color: #3C3C3B;
            font-size: 59px;
            line-height: 50px;
            height: 100%;
            margin: 0;
            padding: 0;
            border: 0;
            outline: 0;
            background: transparent;
        }

        a {
            color: #3C3C3B;
            text-decoration: none;
            font-family: 'Cond', serif;
            font-size: 59px;
            line-height: 52px;
        }

        h3, h3 a {
            font-family: 'Cond Bold', serif;
            color: #3C3C3B;
            font-size: 59px;
            line-height: 52px;
            margin: 0;
            padding: 0;
            display: inline;
        }

        h1 {
            font-family: 'Cond Bold', serif;
            color: #C6B480;
            font-size: 436px;
            line-height: 315px;
            margin: 0;
            padding: 0;
            text-transform: uppercase;
        }

        h2 {
            font-family: 'Cond Bold', serif;
            color: #3C3C3B;
            text-transform: uppercase;
            font-size: 70px;
            line-height: 64px;
            margin: 0;
            padding: 0;
        }

        p {
            margin: 0;
            padding: 0;
        }

        hr {
            margin: 100px 0;
            width: 100%;
            border: 4px solid #C6B480;
            border-radius: 0;
        }

        #content {
            padding: 150px 150px 0 634px;
            z-index: 100;
        }

        #headline {
            margin-bottom: 200px;
            height: 391px;
        }

        #logo {
            position: absolute;
            left: 150px;
            top: 150px;
        }

        #hugo {
            position: absolute;
            right: 170px;
            top: 700px;
        }

        #attendee-info {
            margin-bottom: 100px;
        }

        #attendee-info h2 span, #event-info h2 span {
            font-family: 'Cond', serif;
            text-transform: none;
            font-size: 59px;
            font-weight: normal;
        }

        footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 1000px;
            z-index: -1;
        }

        footer img {
            position: absolute;
            bottom: 0;
            left: 0;
            z-index: -1;
        }

        footer #owner {
            position: absolute;
            bottom: 200px;
            right: 150px;
        }
    </style>
</head>
<body>
<?php if ( file_exists( $img_logo ) ) : ?>
<div id="logo"><img src="<?php echo esc_attr( $img_logo ); ?>" /></div>
<?php endif; ?>
<?php if ( file_exists( $img_hugo ) ) : ?>
<div id="hugo"><img src="<?php echo esc_attr( $img_hugo ); ?>" /></div>
<?php endif; ?>
<div id="content">
    <div id="headline"><h1>Ticket</h1></div>
    <div id="attendee-info">
        <h2>Teilnehmer:in: <span><?php echo esc_html( $reh_tpl['holder_name'] ?: '_________________________' ); ?></span></h2>
        <h2>Ticket-Typ: <span><?php echo esc_html( $reh_tpl['ticket_name'] ); ?></span></h2>
        <h2>Ticketnummer: <span><?php echo esc_html( $reh_tpl['ticket_id'] ); ?></span></h2>
    </div>
    <div id="event-info">
        <h2>Veranstalter:in: <span><?php echo esc_html( $reh_tpl['organizer'] ); ?></span></h2>
        <h2>Datum/Uhrzeit: <span><?php echo $reh_tpl['date'] ? esc_html( $reh_tpl['date'] ) . ' Uhr' : ''; ?></span></h2>
        <h2>Ort: <span><?php echo esc_html( $reh_tpl['location'] ); ?></span></h2>
    </div>
    <hr />
    <div id="checkin-info">
        <p>Bitte beachtet, dass die Anmeldung verbindlich ist und nicht verschoben, storniert oder umgetauscht werden kann!</p>
    </div>
    <hr style="width: 1200px" />
    <div id="contact">
        <h2>Kontakt</h2>
        <p>Rehorik Rösterei &amp; Feinkost GmbH, Am Brixener Hof 6, 93047 Regensburg</p>
        <p>E-Mail: <a href="mailto:events@rehorik.de">events@rehorik.de</a></p>
        <p>Telefon: <a href="tel:0941/7883530">0941 / 788 35 30</a></p>
        <p><a href="https://www.rehorik.de">www.rehorik.de</a></p>
    </div>
</div>
<footer>
    <div id="owner">
        <p>Geschäftsführer: Heiko Rehorik</p>
        <p>Handelsregister Regensburg HRB 18004</p>
    </div>
    <?php if ( file_exists( $img_footer ) ) : ?>
    <img src="<?php echo esc_attr( $img_footer ); ?>" />
    <?php endif; ?>
</footer>
</body>
</html>
