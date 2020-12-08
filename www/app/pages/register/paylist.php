<?php

namespace App\Pages\Register;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\Paginator;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Binding\PropertyBinding as Prop;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \App\Entity\Doc\Document;
use \App\Entity\Customer;
use \App\Helper as H;
use \App\Application as App;
use \App\System;

/**
 * журнал платежей
 */
class PayList extends \App\Pages\Base {

    private $_doc = null;
    private $_ptlist = null;

    /**
     *
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('PayList'))
            return;

        $this->_ptlist = \App\Entity\Pay::getPayTypeList();

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time() + (1 * 24 * 3600)));
        $this->filter->add(new DropDownChoice('fmfund', \App\Entity\MoneyFund::getList(), 0));
        $this->filter->add(new DropDownChoice('fuser', \App\Entity\User::findArray('username', '', 'username'), 0));
        $this->filter->add(new DropDownChoice('ftype', $this->_ptlist, 0));
        $this->filter->add(new AutocompleteTextInput('fcustomer'))->onText($this, 'OnAutoCustomer');

        $doclist = $this->add(new DataView('doclist', new PayListDataSource($this), $this, 'doclistOnRow'));


        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(25);


        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);


        $this->doclist->Reload();
        $this->add(new ClickLink('csv', $this, 'oncsv'));

        $this->_ptlist[0] = '';
    }

    public function filterOnSubmit($sender) {


        $this->docview->setVisible(false);
        $this->doclist->Reload();
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "status=0 and customer_name like " . $text);
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();

        $row->add(new Label('number', $doc->document_number));

        $row->add(new Label('date', date('d-m-Y',  $doc->paydate )));
        $row->add(new Label('notes', $doc->notes));
        $row->add(new Label('amountp', H::fa($doc->amount > 0 ? $doc->amount : "")));
        $row->add(new Label('amountm', H::fa($doc->amount < 0 ? 0 - $doc->amount : "")));

        $row->add(new Label('mf_name', $doc->mf_name));
        $row->add(new Label('username', $doc->username));
        $row->add(new Label('customer_name', $doc->customer_name));
        $row->add(new Label('paytype', $this->_ptlist[$doc->paytype]));


        $row->add(new ClickLink('show', $this, 'showOnClick'));
        $row->add(new ClickLink('del', $this, 'delOnClick'))->setVisible($doc->indoc == 0);
    }

    //просмотр
    public function showOnClick($sender) {


        $this->_doc = Document::load($sender->owner->getDataItem()->document_id);

        if (false == \App\ACL::checkShowDoc($this->_doc, true))
            return;

        $this->docview->setVisible(true);
        $this->docview->setDoc($this->_doc);
    }

    public function delOnClick($sender) {


        $pl = $sender->getOwner()->getDataItem();
        $conn = \ZDB\DB::getConnect();

        $sql = "delete from paylist where pl_id=" . $pl->pl_id;
        $conn->Execute($sql);
        $sql = "select coalesce(abs(sum(amount)),0) from paylist where document_id=" . $pl->document_id;
        $payed = $conn->GetOne($sql);

        $conn->Execute("update documents set payed={$payed} where   document_id =" . $pl->document_id);

        $this->doclist->Reload(true);


        $n = new \App\Entity\Notify();
        $n->user_id = System::getUser()->user_id;
        $n->message = "Удален платеж  <br><br>";
        $n->message .= "Платеж для документа {$pl->document_number} удален пользователем " . System::getUser()->userlogin;

        $n->save();

        $this->resetURL();
    }

    public function oncsv($sender) {
        $list = $this->doclist->getDataSource()->getItems(-1, -1);

        $csv = "Дата;Счет;Приход;Расход;Документ;Создал;Контрагент;Примечание;";
        $csv .= "\n\n";

        foreach ($list as $doc) {

            $csv .= date('Y.m.d', strtotime($doc->paydate)) . ',';
            $csv .= $doc->mf_name . ',';
            $csv .= ($doc->amount > 0 ? $doc->amount : "") . ',';
            $csv .= ($doc->amount < 0 ? 0 - $doc->amount : "" ) . ',';
            $csv .= $doc->document_number . ',';
            $csv .= $doc->username . ',';
            $csv .= $doc->customer_name . ',';
            $csv .= str_replace(',','',$doc->notes) . ',';
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
class PayListDataSource implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();

        $where = " date(paydate) >= " . $conn->DBDate($this->page->filter->from->getDate()) . " and  date(paydate) <= " . $conn->DBDate($this->page->filter->to->getDate());

        $author = $this->page->filter->fuser->getValue();
        $type = $this->page->filter->ftype->getValue();
        $cust = $this->page->filter->fcustomer->getKey();
        $mf = $this->page->filter->fmfund->getValue();


        if ($type > 0) {
            $where .= " and paytype=" . $type;
        }
        if ($cust > 0) {
            $where .= " and d.customer_id=" . $cust;
        }
        if ($mf > 0) {
            $where .= " and p.mf_id=" . $mf;
        }
        if ($author > 0) {
            $where .= " and p.user_id=" . $author;
        }
        if ($user->acltype == 2) {
            $where .= " and d.meta_id in({$user->aclview}) ";
        }
        return $where;
    }

    public function getItemCount() {
        $conn = \ZDB\DB::getConnect();
        $sql = "select coalesce(count(*),0) from documents_view  d join `paylist_view` p on d.`document_id` = p.`document_id` where " . $this->getWhere();
        return $conn->GetOne($sql);
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $conn = \ZDB\DB::getConnect();
        $sql = "select  p.*,d.`customer_name`,d.`meta_id`  from documents_view  d join `paylist_view` p on d.`document_id` = p.`document_id` where " . $this->getWhere() . " order  by  pl_id desc   ";
        if ($count > 0)
            $sql .= " limit {$start},{$count}";

        $docs = \App\Entity\Pay::findBySql($sql);

        return $docs;
    }

    public function getItem($id) {
        
    }

}
