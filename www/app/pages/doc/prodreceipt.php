<?php

namespace App\Pages\Doc;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\SubmitLink;
use \App\Entity\Doc\Document;
use \App\Entity\Item;
use \App\Entity\Store;
use \App\Helper as H;
use \App\System;
use \App\Application as App;

/**
 * Страница  ввода   оприходование с  производства
 */
class ProdReceipt extends \App\Pages\Base {

    public $_itemlist = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid = 0;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();


        $common = System::getOptions("common");


        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new DropDownChoice('parea', \App\Entity\Prodarea::findArray("pa_name", ""), 0));
        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));
        $this->docform->add(new TextInput('notes'));


        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');


        $this->docform->add(new Label('total'));
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutoItem');
        //$this->editdetail->edititem->onChange($this, 'OnChangeItem', true);

        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editsnumber'));
        $this->editdetail->add(new Date('editsdate'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->parea->setValue($this->_doc->headerdata['parea']);

            $this->docform->store->setValue($this->_doc->headerdata['store']);

            foreach ($this->_doc->detaildata as $item) {
                $item = new Item($item);
                $item->old = true;
                $this->_itemlist[$item->item_id] = $item;
            }
        } else {
            $this->_doc = Document::create('ProdReceipt');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }
        $this->calcTotal();
        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc))
            return;
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();


        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::fa($item->price)));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? date('Y-m-d', $item->sdate) : ''));

        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->edit->setVisible($item->old != true);

        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editprice->setText($item->price);
        $this->editdetail->editsnumber->setText($item->snumber);
        $this->editdetail->editsdate->setDate($item->sdate);


        $this->editdetail->edititem->setKey($item->item_id);
        $this->editdetail->edititem->setText($item->itemname);


        $this->_rowid = $item->item_id;
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $item = $sender->owner->getDataItem();
        // unset($this->_itemlist[$item->item_id]);

        $this->_itemlist = array_diff_key($this->_itemlist, array($item->item_id => $this->_itemlist[$item->item_id]));
        $this->calcTotal();
        $this->docform->detail->Reload();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->_rowid = 0;
    }

    public function saverowOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;


        $id = $this->editdetail->edititem->getKey();
        $name = trim($this->editdetail->edititem->getText());
        if ($id == 0 && strlen($name) < 2) {
            $this->setError("Не выбран товар");
            return;
        }
        if ($id == 0) {
            $item = new Item();  // создаем новый
            $item->itemname = $name;
            //todo наценка по дефолту
            $item->save();
            $id = $item->item_id;
        }

        $item = Item::load($id);


        $item->quantity = $this->editdetail->editquantity->getText();
        $item->price = $this->editdetail->editprice->getText();
        if ($item->price == 0) {
            $this->setWarn("Не указана цена");
        }
        $item->snumber = $this->editdetail->editsnumber->getText();
        $item->sdate = $this->editdetail->editsdate->getDate();
        if ($item->sdate == false)
            $item->sdate = '';
        if (strlen($item->snumber) > 0 && strlen($item->sdate) == 0 && $this->_tvars["usesnumber"] == true ) {
            $this->setError("К серии должна быть введена дата");
            return;
        }


        unset($this->_itemlist[$this->_rowid]);
        $this->_itemlist[$item->item_id] = $item;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
        $this->calcTotal();
        //очищаем  форму
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->editdetail->editsnumber->setText("");
        $this->editdetail->editsdate->setText("");
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function savedocOnClick($sender) {
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->notes = $this->docform->notes->getText();
        if ($this->checkForm() == false) {
            return;
        }

        $this->calcTotal();


        $this->_doc->headerdata['parea'] = $this->docform->parea->getValue();
        $this->_doc->headerdata['pareaname'] = $this->docform->parea->getValueName();
        $this->_doc->headerdata['store'] = $this->docform->store->getValue();

        $this->_doc->detaildata = array();
        foreach ($this->_itemlist as $item) {
            $this->_doc->detaildata[] = $item->getData();
        }

        $this->_doc->amount = $this->docform->total->getText();
        $isEdited = $this->_doc->document_id > 0;
        $this->_doc->payamount = $this->_doc->amount;


        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            $this->_doc->save();

            if ($sender->id == 'execdoc') {
                if (!$isEdited)
                    $this->_doc->updateStatus(Document::STATE_NEW);

                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }



            if ($this->_basedocid > 0) {
                $this->_doc->AddConnectedDoc($this->_basedocid);
                $this->_basedocid = 0;
            }

            $conn->CommitTrans();
        } catch (\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
            return;
        }
        App::RedirectBack();
    }

    /**
     * Расчет  итого
     *
     */
    private function calcTotal() {

        $total = 0;

        foreach ($this->_itemlist as $item) {
            $item->amount = $item->price * $item->quantity;
            $total = $total + $item->amount;
        }
        $this->docform->total->setText(H::fa($total));
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {
            $this->setError('Введите номер документа');
        }
        if (count($this->_itemlist) == 0) {
            $this->setError("Не введен ни один  товар");
        }
        if ($this->docform->store->getValue() == 0) {
            $this->setError("Не выбран  склад");
        }


        return !$this->isError();
    }

    public function beforeRender() {
        parent::beforeRender();

        $this->calcTotal();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoItem($sender) {

        $text = Item::qstr('%' . $sender->getText() . '%');
        return Item::findArray('itemname', "(itemname like {$text} or item_code like {$text}) and disabled <> 1");
    }

    public function OnChangeItem($sender) {
        $id = $sender->getKey();

        // $this->editdetail->editprice->setText($stock->partion);

        $this->updateAjax(array('qtystock', 'editprice'));
    }

}
