<?php


defined( 'ABSPATH' )
	or die( 'No direct load ! ' );


/**
 * Generate PDF Class for Contact Form 7.
 *
 * @link https://madeby.restezconnectes.fr/project/send-pdf-for-contact-form-7/
 * @author Florent Maillefaud <contact at restezconnectes.fr> 
 * @since 1.0.0.3
 * @license GPL3 or later
 */

class WPCF7PDF_generate extends cf7_sendpdf {

    static function wpcf7pdf_create_pdf($id, $data, $nameOfPdf, $referenceOfPdf, $createDirectory, $preview = 0) {

        // nothing's here... do nothing...
        if (empty($id) || empty($data))
            return;

        global $wp_session;

        $upload_dir = wp_upload_dir();
        $custom_tmp_path = get_option('wpcf7pdf_path_temp');

        $contact_form = WPCF7_ContactForm::get_instance(esc_html($id));   

        // Definition des dates par defaut
        $dateField = WPCF7PDF_prepare::returndate($id);
        $timeField = WPCF7PDF_prepare::returntime($id);

        // Definition des marges par defaut
        $marginHeader = 10;
        $marginTop = 40;
        $marginBottomHeader = 10;
        $marginLeft = 15;
        $marginRight = 15;

        // On va chercher les TAGS
        $meta_values = get_post_meta(esc_html($id), '_wp_cf7pdf', true);

        require WPCF7PDF_DIR . 'mpdf/vendor/autoload.php';

        if( isset($meta_values['pdf-font'])  ) {
            $fontPdf = esc_html($meta_values['pdf-font']);
        }
        if( isset($meta_values['pdf-fontsize']) && is_numeric($meta_values['pdf-fontsize']) ) {
            $fontsizePdf = esc_html($meta_values['pdf-fontsize']);
        }
        
        if( isset($meta_values["margin_header"]) && $meta_values["margin_header"]!='' ) { $marginHeader = esc_html($meta_values["margin_header"]); }
        if( isset($meta_values["margin_top"]) && $meta_values["margin_top"]!='' ) { $marginTop = esc_html($meta_values["margin_top"]); }
        if( isset($meta_values["margin_left"]) && $meta_values["margin_left"]!='' ) { $marginLeft = esc_html($meta_values["margin_left"]); }
        if( isset($meta_values["margin_right"]) && $meta_values["margin_right"]!='' ) { $marginRight = esc_html($meta_values["margin_right"]); }

        $setDirectionality = 'ltr';
        if( isset($meta_values["set_directionality"]) && $meta_values["set_directionality"]!='' ) {  $setDirectionality = esc_html($meta_values["set_directionality"]);  }

        if( isset($meta_values['pdf-type']) && isset($meta_values['pdf-orientation']) ) {

            $formatPdf = esc_html($meta_values['pdf-type'].$meta_values['pdf-orientation']);
            $mpdfConfig = array(
                'mode' =>
                'utf-8',
                'format' => $formatPdf,
                'margin_header' => $marginHeader,
                'margin_top' => $marginTop,
                'margin_left' => $marginLeft,    	// 15 margin_left
                'margin_right' => $marginRight,    	// 15 margin right
                'default_font' => $fontPdf,
                'default_font_size' => $fontsizePdf,
                'tempDir' => $custom_tmp_path,
            );

        } else if( isset($meta_values['fillable_data']) && $meta_values['fillable_data']== 'true') {

            $mpdfConfig = array(
                'mode' => 'c',
                'format' => $formatPdf,
                'margin_header' => $marginHeader,
                'margin_top' => $marginTop,
                'default_font' => $fontPdf,
                'default_font_size' => $fontsizePdf,
                'tempDir' => $custom_tmp_path,
                'margin_left' => $marginLeft,    	// 15 margin_left
                'margin_right' => $marginRight,    	// 15 margin right
            );

        } else {

            $mpdfConfig = array(
                'mode' => 'utf-8',
                'format' => 'A4-L',
                'margin_header' => $marginHeader,
                'margin_top' => $marginTop,
                'default_font' => $fontPdf,
                'default_font_size' => $fontsizePdf,
                'tempDir' => $custom_tmp_path,
                'margin_left' => $marginLeft,    	// 15 margin_left
                'margin_right' => $marginRight,    	// 15 margin right
            );

        }

        $mpdf = new \Mpdf\Mpdf($mpdfConfig);
        $mpdf->autoScriptToLang = true;
        $mpdf->baseScript = 1;
        $mpdf->autoVietnamese = true;
        $mpdf->autoArabic = true;
        $mpdf->autoLangToFont = true;                    
        $mpdf->SetTitle(get_the_title(esc_html($id)));
        $mpdf->SetCreator(get_bloginfo('name'));
        $mpdf->SetDirectionality($setDirectionality);
        $mpdf->ignore_invalid_utf8 = true;

        $mpdfCharset = 'utf-8';
        if( isset($meta_values["charset"]) && $meta_values["charset"]!='utf-8' ) {
            $mpdfCharset = esc_html($meta_values["charset"]);
        }
        $mpdf->allow_charset_conversion=true;  // Set by default to TRUE
        $mpdf->charset_in=''.$mpdfCharset.'';
        
        if( empty($meta_values["margin_auto_header"]) || ( isset($meta_values["margin_auto_header"]) && $meta_values["margin_auto_header"]=='' ) ) { $meta_values["margin_auto_header"] = 'stretch'; }
        if( empty($meta_values["margin_auto_header"]) || ( isset($meta_values["margin_auto_bottom"]) && $meta_values["margin_auto_bottom"]=='' ) ) { $meta_values["margin_auto_bottom"] = 'stretch'; }

        $mpdf->setAutoTopMargin = esc_html($meta_values["margin_auto_header"]);
        $mpdf->setAutoBottomMargin = esc_html($meta_values["margin_auto_bottom"]);

        if( isset($meta_values['fillable_data']) && $meta_values['fillable_data']== 'true') {
            $mpdf->useActiveForms = true;
        }
        
        if( isset($meta_values['image_background']) && $meta_values['image_background']!='' ) {
            $mpdf->SetDefaultBodyCSS('background', "url('".esc_url($meta_values['image_background'])."')");
            $mpdf->SetDefaultBodyCSS('background-image-resize', 6);
        }
        
        // LOAD a stylesheet
        if( isset($meta_values['stylesheet']) && $meta_values['stylesheet']!='' ) {
            $stylesheet = file_get_contents(esc_url($meta_values['stylesheet']));
            $mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
        }

        // Adding FontAwesome CSS 
        $mpdf->WriteHTML('<style>
        .fa { font-family: fontawesome; }
        .fas { font-family: fontawesome-solid; }
        .fab { font-family: fontawesome-brands;}
        .far { font-family: fontawesome-regular;}
        .dashicons { font-family: dashicons;}
        </style>');

        // Adding Custom CSS            
        if( isset($meta_values['custom_css']) && $meta_values['custom_css']!='' ) {
            $mpdf->WriteHTML('<style>'.esc_html($meta_values['custom_css']).'</style>');
        }

        $entetePage = '';
        if( isset($meta_values["image"]) && !empty($meta_values["image"]) ) {
            if( ini_get('allow_url_fopen')==1) {
                list($width, $height, $type, $attr) = getimagesize(esc_url($meta_values["image"]));
            } else {
                $width = 150;
                $height = 80;
            }
            $imgAlign = 'left';
            if( isset($meta_values['image-alignment']) ) {
                $imgAlign = esc_html($meta_values['image-alignment']);
            }
            if( empty($meta_values['image-width']) ) { $imgWidth = $width; } else { $imgWidth = esc_html($meta_values['image-width']);  }
            if( empty($meta_values['image-height']) ) { $imgHeight = $height; } else { $imgHeight = esc_html($meta_values['image-height']);  }

            $attribut = 'width='.$imgWidth.' height="'.$imgHeight.'"';
            $entetePage = '<div style="text-align:'.$imgAlign.';height:'.$imgHeight.'"><img src="'.esc_url($meta_values["image"]).'" '.$attribut.' /></div>';

            if( isset($meta_values["margin_bottom_header"]) && $meta_values["margin_bottom_header"]!='' ) { $marginBottomHeader = esc_html($meta_values["margin_bottom_header"]); }
            $mpdf->WriteHTML('<p style="margin-bottom:'.$marginBottomHeader.'px;">&nbsp;</p>');
        }
        $mpdf->SetHTMLHeader($entetePage, '', true);

        if( isset($meta_values['footer_generate_pdf']) && $meta_values['footer_generate_pdf']!='' ) {

            $footerText = wp_kses(trim($meta_values['footer_generate_pdf']), WPCF7PDF_prepare::wpcf7pdf_autorizeHtml());
            $footerText = str_replace('[reference]', sanitize_text_field($referenceOfPdf), $footerText);
            $footerText = str_replace('[url-pdf]', esc_url($upload_dir['url'].'/'.$nameOfPdf.'.pdf'), $footerText);
            $footerText = str_replace('[date]', $dateField, $footerText);
            $footerText = str_replace('[time]', $timeField, $footerText);
            $mpdf->SetHTMLFooter($footerText);
        }

        // En cas de saut de page avec le tag [addpage]
        if( isset($data) && stripos($data, '[addpage]') !== false ) {

            $newPage = explode('[addpage]', $data);
            $countPage = count($newPage);

            for($i = 0; $i < ($countPage);  $i++) {
                
                if( $i == 0 ) {
                    // On print la première page
                    $mpdf->WriteHTML($newPage[$i]);
                } else {
                    // On print ensuite les autres pages trouvées
                    if( isset($meta_values["page_header"]) && $meta_values["page_header"]==1) {
                        $mpdf->SetHTMLHeader($entetePage, '', true);
                        $mpdf->AddPage();
                    } else {
                        $mpdf->SetHTMLHeader(); 
                        $mpdf->AddPage('','','','','',15,15,15,15,5,5);
                    }
                    if( isset($meta_values['footer_generate_pdf']) && $meta_values['footer_generate_pdf']!='' ) {
                        $mpdf->SetHTMLFooter($footerText);
                    }
                    $mpdf->WriteHTML($newPage[$i]);
                    if( isset($meta_values["page_header"]) && $meta_values["page_header"]==1) {
                        $mpdf->SetHTMLHeader($entetePage, '', true);
                    } else {
                        $mpdf->SetHTMLHeader();                                 
                    }
                }
                
            }

        } else {

            $data = apply_filters('wpcf7pdf_text', $data, $contact_form);
            $mpdf->WriteHTML($data);

        }
        
        // Option for Protect PDF by Password
        if ( isset($meta_values["protect"]) && $meta_values["protect"]=='true') {
            $pdfPassword = WPCF7PDF_prepare::protect_pdf($id);
            $mpdf->SetProtection(array('print','fill-forms'), $pdfPassword, $pdfPassword, 128);             
        } 

        // Si je suis dans l'admin je génère un preview
        if ( isset($preview) && $preview == 1 ) {

            $mpdf->Output($createDirectory.'/preview-'.esc_html($id).'.pdf', 'F');

        } else {

            $data = wpcf7_mail_replace_tags( wpautop($data) );
            $mpdf->Output($createDirectory.'/'.$nameOfPdf.'.pdf', 'F');
            
            // Je copy le PDF genere
            if( file_exists($createDirectory.'/'.$nameOfPdf.'.pdf') ) {
                copy($createDirectory.'/'.$nameOfPdf.'.pdf', $createDirectory.'/'.$nameOfPdf.'-'.$referenceOfPdf.'.pdf');
            }
        }

    }

    static function wpcf7pdf_create_csv($id, $nameOfPdf, $referenceOfPdf, $createDirectory, $preview = 0) {

        // nothing's here... do nothing...
        if (empty($id))
            return;

        // Je vais chercher le tableau des tags
        $csvTab = cf7_sendpdf::wpf7pdf_tagsparser($id);
        // Je vais chercher la liste des tags pour l'entete du CSV
        $meta_fields = get_post_meta(esc_html($id), '_wp_cf7pdf_fields', true);
        if( isset($meta_fields) ) {
            $entete = array("reference", "date");
            foreach($meta_fields as $field) {
                preg_match_all( '#\[(.*?)\]#', $field, $nameField );
                $nb=count($nameField[1]);
                for($i=0;$i<$nb;$i++) {
                    array_push($entete, $nameField[1][$i]);
                }
            }
        }

        $csvlist = array (
            $entete,
            $csvTab
        );

        if( isset($preview) && $preview == 1 ) {
            $fpCsv = fopen($createDirectory.'/preview-'.esc_html($id).'.csv', 'w+');
        } else {
            $fpCsv = fopen($createDirectory.'/'.$nameOfPdf.'.csv', 'w+');
        }
        
        if( isset($meta_values["csv-separate"]) && !empty($meta_values["csv-separate"]) ) { $csvSeparate = esc_html($meta_values["csv-separate"]); } else { $csvSeparate = ','; }
        foreach ($csvlist as $csvfields) {
            fputcsv($fpCsv, $csvfields, $csvSeparate);
        }
        fclose($fpCsv);

        if( isset($preview) && $preview == 0 ) {
            // Je copy le CSV genere
            if( file_exists($createDirectory.'/'.$nameOfPdf.'.csv') ) {
                copy($createDirectory.'/'.$nameOfPdf.'.csv', $createDirectory.'/'.$nameOfPdf.'-'.$referenceOfPdf.'.csv');
            }
        }
        // END GENERATE CSV

    }
}