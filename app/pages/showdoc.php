<?php

namespace App\Pages;

use \App\Entity\Doc\Document;

//страница  для  загрузки  файла экпорта
class ShowDoc extends \Zippy\Html\WebPage {

    public function __construct($type, $docid) {

        $doc = Document::load($docid);
        if ($doc == null) {
            echo "Не задан  документ";
            return;
        }

        $doc = $doc->cast();
        $filename = $doc->meta_name;

        //$html = \App\Session::getSession()->printform;
        //$xml = \App\Session::getSession()->xmlform;

        $html = $doc->generateReport();

        if (strlen($html) > 0) {


            if ($type == "preview") {
                header("Content-Type: text/html;charset=UTF-8");
                echo $html;
            }
            if ($type == "print") {
                header("Content-Type: text/html;charset=UTF-8");
                echo $html;
            }
            if ($type == "doc") {
                header("Content-type: application/vnd.ms-word");
                header("Content-Disposition: attachment;Filename={$filename}.doc");
                header("Content-Transfer-Encoding: binary");

                echo $html;
            }
            if ($type == "xls") {
                header("Content-type: application/vnd.ms-excel");
                header("Content-Disposition: attachment;Filename={$filename}.xls");
                header("Content-Transfer-Encoding: binary");
                //echo '<meta http-equiv=Content-Type content="text/html; charset=windows-1251">';
                echo $html;
            }
            if ($type == "html") {
                header("Content-type: text/plain");
                header("Content-Disposition: attachment;Filename={$filename}.html");
                header("Content-Transfer-Encoding: binary");

                echo $html;
            }
            if ($type == "xml") {
                $xml = $doc->exportGNAU();
                header("Content-type: text/xml");
                header("Content-Disposition: attachment;Filename={$xml['filename']}");
                header("Content-Transfer-Encoding: binary");

                echo $xml['content'];
            }
            if ($type == "pdf") {
                header("Content-type: application/pdf");
                header("Content-Disposition: attachment;Filename={$filename}.pdf");
                header("Content-Transfer-Encoding: binary");


                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);

                // (Optional) Setup the paper size and orientation
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->set_option('defaultFont', 'DejaVu Sans');
                // Render the HTML as PDF
                $dompdf->render();

                // Output the generated PDF to Browser
                $html = $dompdf->output();
                echo $html;
            }
        } else {
            //$html = "<h4>Печатная форма  не  задана</h4>";
        }

        if ($type == "metaie") { //todo экспорт  файлов  метаобьекта
            $filename = $doc->meta_name . ".zip";


            header("Content-type: application/zip");
            header("Content-Disposition: attachment;Filename={$filename}");
            header("Content-Transfer-Encoding: binary");

            // echo $zip;
        }
        die;
    }

}
