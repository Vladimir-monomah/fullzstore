<?php

namespace App\Widgets;

use \Zippy\Binding\PropertyBinding as Bind;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Label;
use \App\Helper;
use \App\System;
use \App\Entity\Doc\Document;
use \Carbon\Carbon;
use \App\Entity\Item;
use \App\Entity\Stock;
use \App\DataItem;

/**
 * Виджет для просроченных товаров
 */
class WSdate extends \Zippy\Html\PageFragment {

    public function __construct($id) {
        parent::__construct($id);

        $visible = (strpos(System::getUser()->widgets, 'wsdate') !== false || System::getUser()->userlogin == 'admin');

        $conn = $conn = \ZDB\DB::getConnect();
        $data = array();
  
        if ($visible) {
   
            $stock = Stock::find("qty > 0 and sdate is not null  and sdate < now()");


            foreach ($stock as $st) {
                
                $data[$st->stock_id] = $st;
                
                $item = $items[$st->item_id];
 
            }
        }

        $sdlist = $this->add(new DataView('sdlist', new ArrayDataSource($data), $this, 'sdlistOnRow'));
        $sdlist->setPageSize(10);
        $this->add(new \Zippy\Html\DataList\Paginator("sdpag", $sdlist));
        $sdlist->Reload();

 

        if (count($data) == 0 || $visible == false) {
            $this->setVisible(false);
        };
    }

    public function sdlistOnRow($row) {
        $stock = $row->getDataItem();

        $row->add(new Label('storename', $stock->storename));
        $row->add(new Label('itemname', $stock->itemname));
        $row->add(new Label('snumber', $stock->snumber));
        $row->add(new Label('edate', date('Y-m-d', $stock->sdate)));
        $row->add(new Label('qty', Helper::fqty($stock->qty)));
    }

}
