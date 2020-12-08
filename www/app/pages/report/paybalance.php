<?php

namespace App\Pages\Report;

use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\RedirectLink;
use \Zippy\Html\Panel;
use \App\Entity\MoneyFund;
use \App\Entity\Pay;
use \App\Helper as H;
use \App\Application as App;

/**
 * Плптежный  баланс
 */
class PayBalance extends \App\Pages\Base {

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('PayBalance'))
            return;

        $dt = new \Carbon\Carbon;
        $dt->subMonth();
        $from = $dt->startOfMonth()->timestamp;
        $to = $dt->endOfMonth()->timestamp;

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');

        $this->filter->add(new Date('from', $from));
        $this->filter->add(new Date('to', $to));


        $this->add(new \Zippy\Html\Link\ClickLink('autoclick'))->onClick($this, 'OnAutoLoad', true);

        $this->add(new Panel('detail'))->setVisible(false);
        $this->detail->add(new RedirectLink('print', "mfreport"));
        $this->detail->add(new RedirectLink('html', "mfreport"));
        $this->detail->add(new RedirectLink('word', "mfreport"));
        $this->detail->add(new RedirectLink('excel', "mfreport"));
        $this->detail->add(new RedirectLink('pdf', "mfreport"));
        $this->detail->add(new Label('preview'));
        \App\Session::getSession()->issubmit = false;
    }

    public function OnSubmit($sender) {



        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        // \ZippyERP\System\Session::getSession()->storereport = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
        $reportpage = "App/Pages/ShowReport";
        $reportname = "mfreport";


        $this->detail->print->pagename = $reportpage;
        $this->detail->print->params = array('print', $reportname);
        $this->detail->html->pagename = $reportpage;
        $this->detail->html->params = array('html', $reportname);
        $this->detail->word->pagename = $reportpage;
        $this->detail->word->params = array('doc', $reportname);
        $this->detail->excel->pagename = $reportpage;
        $this->detail->excel->params = array('xls', $reportname);
        $this->detail->pdf->pagename = $reportpage;
        $this->detail->pdf->params = array('pdf', $reportname);

        $this->detail->setVisible(true);

        $this->detail->preview->setText("<b >Загрузка...</b>", true);
        \App\Session::getSession()->printform = "";
        \App\Session::getSession()->issubmit = true;
    }

    private function generateReport() {


        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();

        $tin = 0;
        $tout = 0;
        $detail = array();
        $detail2 = array();


        $pl = \App\Entity\Pay::getPayTypeList();

        $conn = \ZDB\DB::getConnect();

        $sql = " 
         SELECT   paytype,coalesce(sum(amount),0) as am   FROM paylist 
             WHERE   
              amount >0 
              AND paydate  >= " . $conn->DBDate($from) . "
              AND  paydate  <= " . $conn->DBDate($to) . "
              GROUP BY  paytype order  by  paytype  
                         
        ";


        $rs = $conn->Execute($sql);

        foreach ($rs as $row) {
            $detail[] = array(
                "in" => H::fa($row['am']),
                "type" => $pl[$row['paytype']]
            );
            $tin += $row['am'];
        }

        $sql = " 
         SELECT   paytype,coalesce(sum(amount),0) as am   FROM paylist 
             WHERE   
              amount < 0 
              AND paydate  >= " . $conn->DBDate($from) . "
              AND  paydate  <= " . $conn->DBDate($to) . "
              GROUP BY  paytype order  by  paytype  
                         
        ";

        $rs = $conn->Execute($sql);

        foreach ($rs as $row) {
            $detail2[] = array(
                "out" => H::fa(0 - $row['am']),
                "type" => $pl[$row['paytype']]
            );
            $tout += 0 - $row['am'];
        }

        $total = $tin - $tout;

        $header = array(
            'datefrom' => date('d.m.Y', $from),
            'dateto' => date('d.m.Y', $to),
            "_detail" => $detail,
            "_detail2" => $detail2,
            'tin' => H::fa($tin),
            'tout' => H::fa($tout),
            'total' => H::fa($total )
        );
        $report = new \App\Report('paybalance.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function OnAutoLoad($sender) {

        if (\App\Session::getSession()->issubmit === true) {
            $html = $this->generateReport();
            \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
            $this->detail->preview->setText($html, true);
            $this->updateAjax(array('preview'));
        }
    }

    public function beforeRender() {
        parent::beforeRender();

        App::addJavaScript("\$('#autoclick').click()", true);
    }

}
