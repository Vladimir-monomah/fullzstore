<?php

namespace App\Pages\Register;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\Paginator;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Binding\PropertyBinding as Prop;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \App\Entity\Doc\Document;
use \App\Helper as H;
use \App\Application as App;
use \App\System;

/**
 * журнал  закупок
 */
class GRList extends \App\Pages\Base {

    private $_doc = null;

    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('GRList'))
            return;

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time() + (1 * 24 * 3600)));

        $this->filter->add(new TextInput('searchnumber'));
        $this->filter->add(new TextInput('searchtext'));
        $this->filter->add(new DropDownChoice('status', array(0 => 'Все', 1 => 'Не проведенные', 2 => 'Неоплаченые'), 0));


        $doclist = $this->add(new DataView('doclist', new GoodsReceiptDataSource($this), $this, 'doclistOnRow'));
        $doclist->setSelectedClass('table-success');

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(25);




        $this->add(new Panel("statuspan"))->setVisible(false);

        $this->statuspan->add(new Form('statusform'));

        // $this->statuspan->statusform->add(new SubmitButton('bsend'))->onClick($this, 'statusOnSubmit');
        //   $this->statuspan->statusform->add(new SubmitButton('bclose'))->onClick($this, 'statusOnSubmit');



        $this->statuspan->add(new \App\Widgets\DocView('docview'));

        $this->filterOnSubmit(null);
        $this->add(new ClickLink('csv', $this, 'oncsv'));
    }

    public function filterOnSubmit($sender) {


        $this->statuspan->setVisible(false);

        $this->doclist->Reload(false);
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();

        $row->add(new Label('number', $doc->document_number));

        $row->add(new Label('date', date('d-m-Y', $doc->document_date)));
        $row->add(new Label('onotes', $doc->notes));
        $row->add(new Label('amount',H::fa( ($doc->payamount > 0) ? $doc->payamount : ($doc->amount > 0 ? $doc->amount : "" ))));

        $row->add(new Label('customer', $doc->customer_name));

        $row->add(new Label('state', Document::getStateName($doc->state)));

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        if ($doc->state == Document::STATE_CANCELED || $doc->state == Document::STATE_EDITED || $doc->state == Document::STATE_NEW) {
            $row->edit->setVisible(true);
        } else {
            $row->edit->setVisible(false);
        }
    }

    public function statusOnSubmit($sender) {

        $state = $this->_doc->state;

        $this->doclist->Reload(false);

        $this->statuspan->setVisible(false);
        //todo  отослать писмо 

        $this->updateStatusButtons();
    }

    public function updateStatusButtons() {


        $state = $this->_doc->state;
    }

    //просмотр
    public function showOnClick($sender) {

        $this->_doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_doc, true))
            return;

        $this->statuspan->setVisible(true);
        $this->statuspan->docview->setDoc($this->_doc);
        $this->doclist->setSelectedRow($sender->getOwner());
        $this->doclist->Reload(false);
        $this->updateStatusButtons();
        $this->goAnkor('dankor');
    }

    public function editOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkEditDoc($doc, true))
            return;

        if ($doc->meta_name == 'GoodsReceipt')
            App::Redirect("\\App\\Pages\\Doc\\GoodsReceipt", $doc->document_id);
        if ($doc->meta_name == 'InvoiceCust')
            App::Redirect("\\App\\Pages\\Doc\\InvoiceCust", $doc->document_id);
    }

    public function oncsv($sender) {
        $list = $this->doclist->getDataSource()->getItems(-1, -1, 'document_id');
        $csv = "";

        foreach ($list as $d) {
            $csv .= date('Y.m.d', $d->document_date) . ',';
            $csv .= $d->document_number . ',';
            $csv .= $d->customer_name . ',';
            $csv .= $d->amount . ',';
            $csv .= str_replace(',','',$d->notes) . ',';
            $csv .= "\n";
        }
        $csv = mb_convert_encoding($csv, "windows-1251", "utf-8");


        header("Content-type: text/csv");
        header("Content-Disposition: attachment;Filename=baylist.csv");
        header("Content-Transfer-Encoding: binary");

        echo $csv;
        flush();
        die;
    }

}

/**
 *  Источник  данных  для   списка  документов
 */
class GoodsReceiptDataSource implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();

        $where = " date(document_date) >= " . $conn->DBDate($this->page->filter->from->getDate()) . " and  date(document_date) <= " . $conn->DBDate($this->page->filter->to->getDate());

        $where .= " and (meta_name  = 'GoodsReceipt' or meta_name  = 'InvoiceCust') ";

        $status = $this->page->filter->status->getValue();

        if ($status == 1) {
            $where .= " and  state <>" . Document::STATE_EXECUTED;
        }
        if ($status == 2) {
            $where .= " and  (payamount > 0 and payamount > payed)";
        }
        if ($status == 0) {
            
        }

        $st = trim($this->page->filter->searchtext->getText());
        if (strlen($st) > 2) {
            $st = $conn->qstr('%' . $st . '%');

            $where .= " and (meta_name  = 'GoodsReceipt' or meta_name  = 'InvoiceCust') and  content like {$st} ";
        }
        $sn = trim($this->page->filter->searchnumber->getText());
        if (strlen($sn) > 1) { // игнорируем другие поля
            $sn = $conn->qstr('%' . $sn . '%');
            $where = " (meta_name  = 'GoodsReceipt' or meta_name  = 'InvoiceCust') and document_number like  {$sn} ";
        }
        if ($user->acltype == 2) {


            $where .= " and meta_id in({$user->aclview}) ";
        }
        return $where;
    }

    public function getItemCount() {
        return Document::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $docs = Document::find($this->getWhere(), "document_date desc,document_id desc", $count, $start);

        //$l = Traversable::from($docs);
        //$l = $l->where(function ($doc) {return $doc->document_id == 169; }) ;
        //$l = $l->select(function ($doc) { return $doc; })->asArray() ;
        return $docs;
    }

    public function getItem($id) {
        
    }

}
