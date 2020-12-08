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
use \App\Entity\Store;
use \App\Entity\Stock;
use \App\Entity\Item;
use \App\Helper as H;
use \App\Application as App;
use \App\System;

/**
 * журнал движения  ТМЦ
 */
class StockList extends \App\Pages\Base {

    private $_doc = null;
    private $_ptlist = null;

    /**
     *
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('StockList'))
            return;
    
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time() + (1 * 24 * 3600)));
        $this->filter->add(new DropDownChoice('fstore', Store::getList(), H::getDefStore()));
        $this->filter->add(new AutocompleteTextInput('fitem'))->onText($this, 'OnAutoItem');

        $doclist = $this->add(new DataView('doclist', new StockListDataSource($this), $this, 'doclistOnRow'));


        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(25);


        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);

  
        

         
    }

    public function filterOnSubmit($sender) {

        if($this->filter->fitem->getKey()==0){
            $this->setError('Не выбран ТМЦ');
            return;
        }
        $this->docview->setVisible(false);
        $this->doclist->Reload();
    }

   
    public function doclistOnRow($row) {
        $doc = $row->getDataItem();

  
        $row->add(new Label('date', date('d-m-Y',  ($doc->document_date))));
        
        $row->add(new Label('partion',  H::fa($doc->partion   )));
        $row->add(new Label('qty',     H::fqty($doc->quantity  )));
        $row->add(new Label('price',  H::fa( $doc->quantity==0 ? '': round(abs($doc->amount/$doc->quantity)) )));

        $row->add(new Label('dnumber', $doc->document_number));
        $row->add(new Label('snumber', $doc->snumber));
   
        $row->add(new ClickLink('show', $this, 'showOnClick'));
 
    }
   
    public function OnAutoItem($sender) {
        $r = array();
 
        $text = Item::qstr('%' . $sender->getText() . '%');
        $list = Item::findArray('itemname', " (itemname like {$text} or item_code like {$text} or bar_code like {$text}  ) ");
        foreach ($list as $k => $v) {
            $r[$k] = $v;
        }
        return $r;
    }

    //просмотр
    public function showOnClick($sender) {
 
        $this->_doc = Document::load($sender->owner->getDataItem()->document_id);

        if (false == \App\ACL::checkShowDoc($this->_doc, true))
            return;

        $this->docview->setVisible(true);
        $this->docview->setDoc($this->_doc);
    }

   
   
}

/**
 *  Источник  данных  для   списка  документов
 */
class StockListDataSource implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();

        $store_id = $this->page->filter->fstore->getValue();
        $item_id = $this->page->filter->fitem->getKey();
        
        $where = " s.item_id = {$item_id} and date(d.document_date) >= " . $conn->DBDate($this->page->filter->from->getDate()) . " and  date(d.document_date) <= " . $conn->DBDate($this->page->filter->to->getDate());
    
        if ($store_id > 0) {
            $where .= " and s.store_id=" . $store_id;
        }
     
        if ($user->acltype == 2) {
            $where .= " and d.meta_id in({$user->aclview}) ";
        }
        return $where;
    }

    public function getItemCount() {
        $conn = \ZDB\DB::getConnect();
        $sql = "select  count(*)  from documents   d ";
        $sql .= " join `entrylist` e on d.`document_id` = e.`document_id` " ;
        $sql .= " join `store_stock` s on s.`stock_id` = e.`stock_id` " ;
        $sql .= " where " . $this->getWhere()  ;
        return $conn->GetOne($sql);
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $conn = \ZDB\DB::getConnect();
        $sql = "select e.entry_id, e.quantity,  e.amount  , d.document_id, d.document_number,d.document_date,s.partion,s.snumber from documents   d ";
        $sql .= " join `entrylist` e on d.`document_id` = e.`document_id` " ;
        $sql .= " join `store_stock` s on s.`stock_id` = e.`stock_id` " ;
        $sql .= " where " . $this->getWhere() . " order  by  entry_id desc   ";
        if ($count > 0)
            $sql .= " limit {$start},{$count}";

        $docs = \App\Entity\Entry::findBySql($sql);

        return $docs;
    }

    public function getItem($id) {
        
    }

}
