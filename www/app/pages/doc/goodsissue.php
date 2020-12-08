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
use \App\Entity\Customer;
use \App\Entity\Doc\Document;
use \App\Entity\Item;
use \App\Entity\Store;
use \App\Entity\MoneyFund;
use \App\Helper as H;
use \App\System;
use \App\Application as App;

/**
 * Страница  ввода  расходной накладной
 */
class GoodsIssue extends \App\Pages\Base {

    public $_itemlist = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid = 0;
    private $_orderid = 0;
    private $_prevcust = 0;   // преыдущий контрагент

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new Date('sent_date'));
        $this->docform->add(new Date('delivery_date'));

        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), H::getDefMF()));
        $this->docform->add(new TextInput('paynotes'));
        $this->docform->add(new Label('discount'))->setVisible(false);
        $this->docform->add(new TextInput('editpaydisc'));
        $this->docform->add(new SubmitButton('bpaydisc'))->onClick($this, 'onPayDisc');
        $this->docform->add(new Label('paydisc', 0));
        $this->docform->add(new CheckBox('prepaid'))->onChange($this, 'OnPrepaid');

        $this->docform->add(new TextInput('editpayamount'));
        $this->docform->add(new SubmitButton('bpayamount'))->onClick($this, 'onPayAmount');
        $this->docform->add(new TextInput('editpayed', "0"));
        $this->docform->add(new SubmitButton('bpayed'))->onClick($this, 'onPayed');
        $this->docform->add(new Label('payed', 0));
        $this->docform->add(new Label('payamount', 0));

        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');


        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()))->onChange($this, 'OnChangeStore');

        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');

        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnChangeCustomer');
        $this->docform->add(new DropDownChoice('pricetype', Item::getPriceTypeList()));
        $this->docform->add(new DropDownChoice('emp', \App\Entity\Employee::findArray('emp_name', '', 'emp_name')));

        $this->docform->add(new DropDownChoice('delivery', array(1 => 'Самовывоз', 2 => 'Курьер', 3 => 'Почта'), 1))->onChange($this, 'OnDelivery');

        $this->docform->add(new TextInput('order'));

        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('ship_number'));
        $this->docform->add(new TextInput('ship_address'));



        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('senddoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new Label('total'));


        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editserial'));

        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->editdetail->edittovar->onChange($this, 'OnChangeItem', true);



        $this->editdetail->add(new Label('qtystock'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');

        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        if ($docid > 0) {    //загружаем   содержимок  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->pricetype->setValue($this->_doc->headerdata['pricetype']);
            $this->docform->total->setText($this->_doc->amount);

            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->sent_date->setDate($this->_doc->headerdata['sent_date']);
            $this->docform->delivery_date->setDate($this->_doc->headerdata['delivery_date']);
            $this->docform->ship_number->setText($this->_doc->headerdata['ship_number']);
            $this->docform->ship_address->setText($this->_doc->headerdata['ship_address']);
            $this->docform->emp->setValue($this->_doc->headerdata['emp_id']);
            $this->docform->delivery->setValue($this->_doc->headerdata['delivery']);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->paynotes->setText($this->_doc->headerdata['paynotes']);
            $this->docform->payamount->setText($this->_doc->payamount);
            $this->docform->editpayamount->setText($this->_doc->payamount);
            $this->docform->paydisc->setText($this->_doc->headerdata['paydisc']);
            $this->docform->editpaydisc->setText($this->_doc->headerdata['paydisc']);
            $this->docform->payed->setText($this->_doc->headerdata['payed']);
            $this->docform->editpayed->setText($this->_doc->headerdata['payed']);
            $this->docform->prepaid->setChecked($this->_doc->headerdata['prepaid']);
            $this->OnPrepaid($this->docform->prepaid);


            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->order->setText($this->_doc->headerdata['order']);
            $this->_orderid = $this->_doc->headerdata['order_id'];
            $this->_prevcust = $this->_doc->customer_id;

            $this->OnChangeCustomer($this->docform->customer);

            foreach ($this->_doc->detaildata as $item) {
                $item = new Item($item);
                $this->_itemlist[$item->item_id] = $item;
            }
        } else {
            $this->_doc = Document::create('GoodsIssue');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'Order') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);
                        $this->OnChangeCustomer($this->docform->customer);

                        $this->docform->pricetype->setValue($basedoc->headerdata['pricetype']);
                        $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->_orderid = $basedocid;
                        $this->docform->order->setText($basedoc->document_number);
                        $this->docform->ship_address->setText($basedoc->headerdata['address']);
                        $this->docform->delivery->setValue($basedoc->headerdata['delivery']);
                        $this->docform->sent_date->setDate($basedoc->headerdata['sent_date']);
                        $this->docform->delivery_date->setDate($basedoc->headerdata['delivery']);

                        $notfound = array();
                        $order = $basedoc->cast();

                        $ttn = false;
                        //проверяем  что уже есть отправка
                        $list = $order->ConnectedDocList();
                        foreach ($list as $d) {
                            if ($d->meta_name == 'GoodsIssue') {
                                $ttn = true;
                            }
                        }

                        if ($ttn) {
                            $this->setWarn('У заказа  уже  есть отправка');
                        }
                        $this->docform->total->setText($order->amount);

                        $this->OnChangeCustomer($this->docform->customer);
                        $this->calcPay() ;
                        
                        foreach ($order->detaildata as $item) {
                            $item = new Item($item);
                            $this->_itemlist[$item->item_id] = $item;
                        }
                        
                    }
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc))
            return;

        $this->OnDelivery($this->docform->delivery);
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('tovar', $item->itemname));

        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? date('Y-m-d', $item->sdate) : ''));

        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::fa($item->price)));

        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        //  $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;

        $tovar = $sender->owner->getDataItem();
        // unset($this->_itemlist[$tovar->tovar_id]);

        $this->_itemlist = array_diff_key($this->_itemlist, array($tovar->item_id => $this->_itemlist[$tovar->item_id]));
        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("0");
        $this->editdetail->qtystock->setText("");
        $this->docform->setVisible(false);
        $this->_rowid = 0;
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);


        $this->editdetail->edittovar->setKey($item->item_id);
        $this->editdetail->edittovar->setText($item->itemname);

        $this->OnChangeItem($this->editdetail->edittovar);
        
        $this->editdetail->editprice->setText($item->price);
        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editserial->setText($item->serial);
        
        $this->_rowid = $item->item_id;
    }

    public function saverowOnClick($sender) {

        $id = $this->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("Не выбран товар");
            return;
        }
        $item = Item::load($id);

        $item->quantity = $this->editdetail->editquantity->getText();
        $item->snumber = $this->editdetail->editserial->getText();
        $qstock = $this->editdetail->qtystock->getText();
 
        $item->price = $this->editdetail->editprice->getText();

        if($item->quantity > $qstock){
            $this->setWarn('Введено  больше  товара  чем  в  наличии');
        }
          
        if(strlen($item->snumber)==0 && $item->useserial==1 && $this->_tvars["usesnumber"] == true ){
            $this->setError("Товар требует ввода партии производителя");
            return;
        }
       
        if ($this->_tvars["usesnumber"] == true && $item->useserial ==1) {
            $slist=  $item->getSerials($store_id);
            
            if(in_array($item->snumber,$slist) == false){
                $this->setWarn('Неверный номер серии');
            }
        }
           
        unset($this->_itemlist[$this->_rowid]);
        $this->_itemlist[$item->item_id] = $item;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->editdetail->editserial->setText("");
        $this->calcTotal();
        $this->calcPay();
      
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->notes = $this->docform->notes->getText();
     //   $this->_doc->order = $this->docform->order->getText();

        $this->_doc->customer_id = $this->docform->customer->getKey();
        $this->_doc->payamount = $this->docform->payamount->getText();

        $this->_doc->headerdata['payed'] = $this->docform->payed->getText();
        $this->_doc->headerdata['paydisc'] = $this->docform->paydisc->getText();
        $this->_doc->headerdata['prepaid'] = $this->docform->prepaid->isChecked();
        if ($this->_doc->headerdata['prepaid'] == 1) {
            $this->_doc->headerdata['paydisc'] = 0;
            $this->_doc->headerdata['payed'] = 0;
            $this->_doc->payamount = 0;
        }


        if ($this->checkForm() == false) {
            return;
        }
        $order = Document::load($this->_orderid);




        $this->_doc->headerdata['order_id'] = $this->_orderid;
        $this->_doc->headerdata['order'] = $this->docform->order->getText();
        $this->_doc->headerdata['ship_address'] = $this->docform->ship_address->getText();
        $this->_doc->headerdata['ship_number'] = $this->docform->ship_number->getText();
        $this->_doc->headerdata['delivery'] = $this->docform->delivery->getValue();
        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['emp_id'] = $this->docform->emp->getValue();
        $this->_doc->headerdata['emp_name'] = $this->docform->emp->getValueName();
        $this->_doc->headerdata['pricetype'] = $this->docform->pricetype->getValue();
        $this->_doc->headerdata['pricetypename'] = $this->docform->pricetype->getValueName();
        $this->_doc->headerdata['delivery_date'] = $this->docform->delivery_date->getDate();
        $this->_doc->headerdata['sent_date'] = $this->docform->sent_date->getDate();
        $this->_doc->headerdata['order_id'] = $this->_orderid;
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();
        $this->_doc->headerdata['paynotes'] = $this->docform->paynotes->getText();


        $this->_doc->detaildata = array();
        foreach ($this->_itemlist as $tovar) {
            $this->_doc->detaildata[] = $tovar->getData();
        }

        $this->_doc->amount = $this->docform->total->getText();

        $isEdited = $this->_doc->document_id > 0;



        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            $this->_doc->save();
            if ($sender->id == 'execdoc') {
                if (!$isEdited)
                    $this->_doc->updateStatus(Document::STATE_NEW);

                $this->_doc->updateStatus(Document::STATE_EXECUTED);

                $order = Document::load($this->_doc->headerdata['order_id']);
                if ($order instanceof Document) {
                    $order->updateStatus(Document::STATE_DELIVERED);
                }
            } else
            if ($sender->id == 'senddoc') {
                if (!$isEdited)
                    $this->_doc->updateStatus(Document::STATE_NEW);

                $this->_doc->updateStatus(Document::STATE_EXECUTED);
                $this->_doc->updateStatus(Document::STATE_INSHIPMENT);
                $this->_doc->headerdata['sent_date'] = time();
                $this->_doc->save();

                $order = Document::load($this->_doc->headerdata['order_id']);
                if ($order instanceof Document) {
                    $order->updateStatus(Document::STATE_INSHIPMENT);
                }
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
                if ($order instanceof Document) {
                    $order->updateStatus(Document::STATE_INPROCESS);
                }
            }

            if ($this->_basedocid > 0) {
                $this->_doc->AddConnectedDoc($this->_basedocid);
                $this->_basedocid = 0;
            }
            $conn->CommitTrans();
            if ($isEdited)
                App::RedirectBack();
            else
                App::Redirect("\\App\\Pages\\Register\\GIList");
        } catch (\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
            return;
        }
    }

    public function onPayAmount($sender) {
        $this->docform->payamount->setText($this->docform->editpayamount->getText());
        $this->goAnkor("tankor");
        
    }

    public function onPayed($sender) {
        $this->docform->payed->setText(H::fa($this->docform->editpayed->getText()));
        $payed   = $this->docform->payed->getText();
        $payamount   = $this->docform->payamount->getText();
        if($payed >$payamount){
            $this->setWarn('Внесена  сумма  больше  необходимой');
        }
        else {
            $this->goAnkor("tankor");    
        }
   }

    public function onPayDisc() {
        $this->docform->paydisc->setText($this->docform->editpaydisc->getText());
        $this->calcPay();
        $this->goAnkor("tankor");
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


        $disc = 0;

        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);

            if ($customer->discount > 0) {
                $disc = round($total * ($customer->discount / 100));
            } else if ($customer->bonus > 0) {
                if ($total >= $customer->bonus) {
                    $disc = $customer->bonus;
                } else {
                    $disc = $total;
                }
            }
        }


        $this->docform->paydisc->setText($disc);
        $this->docform->editpaydisc->setText($disc);
    }

    private function calcPay() {
        $total = $this->docform->total->getText();
        $disc = $this->docform->paydisc->getText();

        $this->docform->editpayamount->setText(H::fa($total - $disc));
        $this->docform->payamount->setText(H::fa($total - $disc));
        $this->docform->editpayed->setText(H::fa($total - $disc));
        $this->docform->payed->setText(H::fa($total - $disc));
    }

    public function OnPrepaid($sender) {
        $b = $sender->isChecked();
        if ($b) {
            $this->docform->payed->setVisible(false);
            $this->docform->payamount->setVisible(false);
            $this->docform->paydisc->setVisible(false);
        } else {
            $this->docform->payed->setVisible(true);
            $this->docform->payamount->setVisible(true);
            $this->docform->paydisc->setVisible(true);
        }
    }

    public function addcodeOnClick($sender) {
        $code = trim($this->docform->barcode->getText());
        $this->docform->barcode->setText('');
        if ($code == '')
            return;


        $code_ = Item::qstr($code);
        $item = Item::getFirst("  (item_code = {$code_} or bar_code = {$code_})");



        if ($item == null) {
            $this->setError("Товар с  кодом '{$code}' не  найден");
            return;
        }





        $store = $this->docform->store->getValue();

        $qty = $item->getQuantity($store);
        if ($qty <= 0) {
            $this->setError("Товара {$item->itemname} нет на складе");
        }
 

        if ($this->_itemlist[$item->item_id] instanceof Item) {
            $this->_itemlist[$item->item_id]->quantity += 1;
        } else {
            

             $price = $item->getPrice($this->docform->pricetype->getValue(), $store_id);
             $item->price = $price;
             $item->quantity =1;
            
             if ($this->_tvars["usesnumber"] == true && $item->useserial ==1) {

                $serial='';
                $slist=  $item->getSerials($store_id);
                if(count($slist) == 1){
                   $serial = array_pop($slist) ;
                     
                }
                 
                 

                if(strlen($serial)==0){
                   $this->setWarn('Нужно ввести  номер партии производителя'); 
                   $this->editdetail->setVisible(true);
                   $this->docform->setVisible(false);
                   
                   
                   $this->editdetail->edittovar->setKey($item->item_id);
                   $this->editdetail->edittovar->setText($item->itemname);
                   $this->editdetail->editserial->setText('');
                   $this->editdetail->editquantity->setText('1');
                   $this->editdetail->editprice->setText($item->price);

 
                   
                   return;
                }
                else {
                    $item->snumber= $serial;
 
                }
                
            }
            $this->_itemlist[$item->item_id] = $item;
        }
        $this->docform->detail->Reload();
        $this->calcTotal() ;
        $this->calcPay() ;

        $this->_rowid = 0;
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
            $this->setError("Не веден ни один  товар");
        }
        if ($this->docform->store->getValue() == 0) {
            $this->setError("Не выбран  склад");
        }
        if ($this->_doc->payamount > 0 && $this->_doc->headerdata['payed'] == 0) {
            $this->setError("Не указан  способ  оплаты");
        }
        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnChangeStore($sender) {
        //очистка  списка  товаров
        $this->_itemlist = array();
        $this->docform->detail->Reload();
    }

    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);
        $store_id = $this->docform->store->getValue();

        $price = $item->getPrice($this->docform->pricetype->getValue(), $store_id);
        $qty = $item->getQuantity($store_id);

        $this->editdetail->qtystock->setText(H::fqty($qty));
        $this->editdetail->editprice->setText($price);
        if ($this->_tvars["usesnumber"] == true && $item->useserial ==1) {

            $serial='';
            $slist=  $item->getSerials($store_id);
            if(count($slist) == 1){
               $serial = array_pop($slist) ;
                 
            }
            $this->editdetail->editserial->setText($serial);
        }
        

        $this->updateAjax(array('qtystock', 'editprice', 'editserial'));
    }

    public function OnAutoItem($sender) {
        $store_id = $this->docform->store->getValue();
        $text = trim($sender->getText());
        return Item::findArrayAC($text );
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "status=0 and customer_name like " . $text);
    }

    public function OnChangeCustomer($sender) {
        $this->docform->discount->setVisible(false);

        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);
            if ($customer->discount > 0) {
                $this->docform->discount->setText("Постоянная скидка " . $customer->discount . '%');
                $this->docform->discount->setVisible(true);
            } else if ($customer->bonus > 0) {
                $this->docform->discount->setText("Бонусы " . $customer->bonus);
                $this->docform->discount->setVisible(true);
            }
            $this->docform->ship_address->setText($customer->address);
        }
        if ($this->_prevcust != $customer_id) {//сменился контрагент
            $this->_prevcust = $customer_id;
            $this->calcTotal();

            $this->calcPay();
        }
    }

    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docform->setVisible(false);

        $this->editcust->editcustname->setText('');
        $this->editcust->editphone->setText('');
    }

    public function savecustOnClick($sender) {
        $custname = trim($this->editcust->editcustname->getText());
        if (strlen($custname) == 0) {
            $this->setError("Не введено имя");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;
        $cust->phone = $this->editcust->editcustname->getText();
        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);

        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->discount->setVisible(false);
        $this->_discount = 0;
    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function OnDelivery($sender) {

        if ($sender->getValue() == 2 || $sender->getValue() == 3) {
            $this->docform->senddoc->setVisible(true);
            $this->docform->execdoc->setVisible(false);
            $this->docform->ship_address->setVisible(true);
            $this->docform->ship_number->setVisible(true);
            $this->docform->sent_date->setVisible(true);
            $this->docform->sent_date->setVisible(true);
            $this->docform->delivery_date->setVisible(true);
            $this->docform->emp->setVisible(true);
        } else {
            $this->docform->senddoc->setVisible(false);
            $this->docform->execdoc->setVisible(true);
            $this->docform->ship_address->setVisible(false);
            $this->docform->ship_number->setVisible(false);
            $this->docform->sent_date->setVisible(false);
            $this->docform->sent_date->setVisible(false);
            $this->docform->delivery_date->setVisible(false);
            $this->docform->emp->setVisible(false);
        }
    }

}
