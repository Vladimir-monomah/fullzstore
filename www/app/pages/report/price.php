<?php

namespace App\Pages\Report;

use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Label;
use \Zippy\Html\Link\RedirectLink;
use \Zippy\Html\Panel;
use \App\Entity\Item;
use \App\Helper as H;

/**
 * Прайсы
 */
class Price extends \App\Pages\Base {

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('ItemActivity'))
            return;

        $option = \App\System::getOptions('common');

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new CheckBox('price1'))->setVisible(strlen($option['price1']) > 0);
        $this->filter->add(new CheckBox('price2'))->setVisible(strlen($option['price2']) > 0);
        $this->filter->add(new CheckBox('price3'))->setVisible(strlen($option['price3']) > 0);
        $this->filter->add(new CheckBox('price4'))->setVisible(strlen($option['price4']) > 0);
        $this->filter->add(new CheckBox('price5'))->setVisible(strlen($option['price5']) > 0);

        $this->_tvars['price1name'] = $option['price1'];
        $this->_tvars['price2name'] = $option['price2'];
        $this->_tvars['price3name'] = $option['price3'];
        $this->_tvars['price4name'] = $option['price4'];
        $this->_tvars['price5name'] = $option['price5'];

        $this->add(new Panel('detail'))->setVisible(false);
        $this->detail->add(new RedirectLink('print', "price"));
        $this->detail->add(new RedirectLink('excel', "price"));
        $this->detail->add(new RedirectLink('pdf', "price"));
        $this->detail->add(new Label('preview'));
    }

    public function OnSubmit($sender) {



        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        $reportpage = "App/Pages/ShowReport";
        $reportname = "price";


        $this->detail->print->pagename = $reportpage;
        $this->detail->print->params = array('print', $reportname);
        $this->detail->excel->pagename = $reportpage;
        $this->detail->excel->params = array('xls', $reportname);
        $this->detail->pdf->pagename = $reportpage;
        $this->detail->pdf->params = array('pdf', $reportname);

        $this->detail->setVisible(true);
    }

    private function generateReport() {

        $option = \App\System::getOptions('common');

        $isp1 = $this->filter->price1->isChecked();
        $isp2 = $this->filter->price2->isChecked();
        $isp3 = $this->filter->price3->isChecked();
        $isp4 = $this->filter->price4->isChecked();
        $isp5 = $this->filter->price5->isChecked();

        $detail = array();

        $items = Item::find("disabled <>1 and detail like '%<pricelist>1</pricelist>%'", "cat_name,itemname");



        foreach ($items as $item) {
            $detail[] = array(
                "code" => $item->item_code,
                "name" => $item->itemname,
                "cat" => $item->cat_name,
                "msr" => $item->msr,
                "price1" =>H::fa( $isp1 ? round($this->checkPrice($item->item_id, $item->price1)) : ""),
                "price2" =>H::fa( $isp2 ? round($this->checkPrice($item->item_id, $item->price2)) : ""),
                "price3" =>H::fa( $isp3 ? round($this->checkPrice($item->item_id, $item->price3)) : ""),
                "price4" =>H::fa( $isp4 ? round($this->checkPrice($item->item_id, $item->price4)) : ""),
                "price5" =>H::fa( $isp5 ? round($this->checkPrice($item->item_id, $item->price5)) : "")
            );
        }

        $header = array(
            "_detail" => $detail,
            "price1name" => $isp1 ? $option['price1'] : "",
            "price2name" => $isp2 ? $option['price2'] : "",
            "price3name" => $isp3 ? $option['price3'] : "",
            "price4name" => $isp4 ? $option['price4'] : "",
            "price5name" => $isp5 ? $option['price5'] : "",
            'date' => date('d.m.Y', time())
        );
        $report = new \App\Report('price.tpl');

        $html = $report->generate($header);

        return $html;
    }

    //проверка на наценку
    private function checkPrice($item_id, $price) {
        if (strpos($price, '%') > 0) {
            $price = doubleval(str_replace('%', '', $price));
            $conn = \ZDB\DB::getConnect();

            $sql = "  select partion  from  store_stock where   item_id = {$item_id} order by stock_id desc limit 1";
            $partionprice = $conn->GetOne($sql);
            if ($partionprice > 0) {
                return $partionprice + (int) $partionprice / 100 * $price;
            } else {
                return 0;
            }
        } else {
            return H::fa($price);
        }
    }

}
