<?php
/**
 * Plugin Name: htmlToPDF
 * Plugin URI: http://www.jeremylipszyc.be/plugins/htmlToPDF
 * Description: create a pdf copy from a html page
 * Version: 2.0
 * Author: Jeremy Lipszyc
 * Author URI: http://www.jeremylipszyc.be
 */

require_once( __DIR__ .'/vendor/autoload.php');
include 'PDFMerger.php';

use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;

add_action('publish_page', 'savepdf');
add_action('publish_post', 'savepdf');
add_action( 'wp_enqueue_scripts', 'capitaine_assets' );
add_action('wp_ajax_telechargerPDF', 'telechargerPDF');
add_action('wp_ajax_mergePDF', 'mergePdfs');

if(isset($_GET['formPDF'])){
    $formPdf = $_GET['formPDF'];
    if(empty($formPdf)) 
    {
        echo("You didn't select any pages.");
    } 
    else 
    {
        $N = count($formPdf);
        $pdf = new PDFMerger;
        $content_directory = WP_CONTENT_DIR . '/uploads/pdf';
        for($i=0; $i < $N; $i++)
        {
            $id = $formPdf[$i];
            $file = $content_directory."/".$id.'.pdf';
            $pdf->addPDF($file, "all"); 
        }
        $pdf->merge('download', 'villages.pdf'); // force download
    }
}

if(isset($_POST['pdfID'])){
    $content_directory = WP_CONTENT_DIR . '/uploads/pdf';
    $file = $content_directory."/".$_POST['pdfID'].'.pdf';
    if(file_exists($file)){
        //Define header information
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: 0");
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Content-Length: ' . filesize($file));
        header('Pragma: public');

        //Clear system output buffer
        flush();

        //Read the size of the file
        readfile($file);

        //Terminate from the script
        die();
    }
    else{
        //savepdf($_POST['pdfID']);
        error_log('erreur htmlToPDF envoie du pdf id:'.$_POST['pdfID']);
    }
}

function savepdf($id){
    
    $apiKey = 'api_F4D813201D2647399A6A047DED858936';
    $url = "https://api.sejda.com/v2/html-pdf";
    $content = json_encode(array('url' => get_permalink($id)));

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    "Content-type: application/json",
    "Authorization: Token: " . $apiKey));

    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

    $response = curl_exec($curl);

    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($status == 200) {
        $content_directory = WP_CONTENT_DIR . '/uploads/pdf';
        $file = $content_directory."/".$id.'.pdf';
        
        $fp = fopen($file, 'w+');
        fputs($fp, $response);
        fclose($fp);
        
    } else {
        print("Error: failed with status $status, response $response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
    }
    //wp_die();
}

 /** Step 2 (from text above). */
add_action( 'admin_menu', array('creation_plugin', 'my_plugin_menu' ));
add_shortcode("htmlToPDF", array('creation_plugin', "shortcode_button"));
add_shortcode("liste_pages", array('creation_plugin', "liste_pages_checkbox"));

class creation_plugin{
    /** Step 1. */
    function my_plugin_menu() {
        add_media_page( 'My Plugin Options', 'HTML to PDF', 'manage_options', 'my-unique-identifier', array('creation_plugin', 'my_plugin_options') );
    }

    /** Step 3. */
    function my_plugin_options() {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        ?>
        <h1>Bienvenue dans le plugin HTML to PDF</h1>
    
         <?php
        
    }

    function shortcode_button(){
        $id = get_the_ID();
        echo '<form name="myform" action="htmlToPDF.php" method="post">
        <input type="hidden" name="pdfID" value="'.$id.'"/>
        <button type=submit">Save as pdf</button>
        </form>';
    }

    function liste_pages_checkbox(){
        $form = "<form action='htmlToPDF' method='get'><ul>";
        $pages = get_pages();
        foreach ($pages as $page){
            $form .= "<li><input type='checkbox' name='formPDF[]' value=". $page->ID ."><label for=". $page->ID .">". $page->post_title ."</label></li>";
        }
        $form .= "</ul><br><input type='submit' id='mergePDF' name='mergePDF' value='télécharger pdf'></form>";
        return $form;
    }

}

?>