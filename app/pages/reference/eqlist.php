<?php

namespace App\Pages\Reference;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \App\Entity\Equipment;
use \App\Entity\Employee;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Binding\PropertyBinding as Bind;
use \App\Helper;
use \App\System;
use \Zippy\Html\Link\BookmarkableLink;

//справочник  оборудования
class EqList extends \App\Pages\Base {

    private $_item;
    public $_uselist = array();

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('EqList'))
            return;

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new DropDownChoice('searchemp', Employee::findArray("emp_name", "", "emp_name"), 0));
        $this->filter->add(new CheckBox('showdis'));


        $this->add(new Panel('itemtable'))->setVisible(true);
        $this->itemtable->add(new DataView('eqlist', new EQDS($this), $this, 'eqlistOnRow'));
        $this->itemtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');

        $this->itemtable->eqlist->setPageSize(25);
        $this->itemtable->add(new \Zippy\Html\DataList\Paginator('pag', $this->itemtable->eqlist));
        $this->itemtable->eqlist->setSelectedClass('table-success');
        $this->itemtable->eqlist->Reload();

        $this->add(new Form('itemdetail'))->setVisible(false);
        $this->itemdetail->add(new TextInput('editname'));


        $this->itemdetail->add(new TextInput('editserial'));
        $this->itemdetail->add(new DropDownChoice('editemp', Employee::findArray("emp_name", "", "emp_name"), 0));
        $this->itemdetail->add(new TextInput('editcode'));
        $this->itemdetail->add(new TextArea('editdescription'));
        $this->itemdetail->add(new CheckBox('editdisabled'));


        $this->itemdetail->add(new SubmitButton('save'))->onClick($this, 'OnSubmit');
        $this->itemdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        $this->add(new Panel('usetable'))->setVisible(false);
        $this->usetable->add(new Label('usename'));
        $this->usetable->add(new ClickLink('back'))->onClick($this, 'cancelOnClick');
        $this->usetable->add(new DataView('uselist', new ArrayDataSource($this, '_uselist'), $this, 'uselistOnRow'));
    }

    public function eqlistOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('eq_name', $item->eq_name));
        $row->add(new Label('code', $item->code));
        $row->add(new Label('serial', $item->serial));

        $row->add(new ClickLink('use'))->onClick($this, 'useOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('EqList'))
            return;

        $item = $sender->owner->getDataItem();
        //проверка на партии
        if ($item->checkDelete()) {
            Equipment::delete($item->eq_id);
        } else {
            $this->setError("Нельзя удалить   ");
            return;
        }



        $this->itemtable->eqlist->Reload();
    }

    public function useOnClick($sender) {
        $this->itemtable->setVisible(false);
        $this->usetable->setVisible(true);
        $item = $sender->getOwner()->getDataItem();
        $this->usetable->usename->setText($item->eq_name);
        $this->_uselist = array();

        $list = \App\Entity\Doc\Document::find("meta_name='task' and state not in(2,3,1,9) ", "document_date desc");


        foreach ($list as $task) {
            foreach ($task->detaildata as $value) {
                if ($value['eq_id'] > 0) {

                    $it = new \App\DataItem(array(
                        "usetask" => $task->document_number,
                        "useplace" => $value['eq_name']
                    ));
                    $this->_uselist[] = $it;
                }
            }
        }

        $this->usetable->uselist->Reload();
    }

    public function uselistOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('usetask', $item->usetask));
        $row->add(new Label('useplace', $item->useplace));
    }

    public function editOnClick($sender) {
        $this->_item = $sender->owner->getDataItem();
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);

        $this->itemdetail->editname->setText($this->_item->eq_name);

        $this->itemdetail->editemp->setValue($this->_item->emp_id);
        $this->itemdetail->editdisabled->setChecked($this->_item->disabled);

        $this->itemdetail->editdescription->setText($this->_item->description);
        $this->itemdetail->editcode->setText($this->_item->code);
        $this->itemdetail->editserial->setText($this->_item->serial);
    }

    public function addOnClick($sender) {
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);
        // Очищаем  форму
        $this->itemdetail->clean();
        $this->_item = new Equipment();
    }

    public function cancelOnClick($sender) {
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
        $this->usetable->setVisible(false);
    }

    public function OnFilter($sender) {
        $this->itemtable->eqlist->Reload();
    }

    public function OnSubmit($sender) {
        if (false == \App\ACL::checkEditRef('EqList'))
            return;

        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);

        $this->_item->eq_name = $this->itemdetail->editname->getText();
        $this->_item->emp_id = $this->itemdetail->editemp->getValue();
        $this->_item->emp_name = $this->itemdetail->editemp->getValueName();

        $this->_item->code = $this->itemdetail->editcode->getText();

        $this->_item->serial = $this->itemdetail->editserial->getText();
        $this->_item->description = $this->itemdetail->editdescription->getText();
        $this->_item->disabled = $this->itemdetail->editdisabled->isChecked() ? 1 : 0;

        $this->_item->Save();

        $this->itemtable->eqlist->Reload();
    }

}

class EQDS implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $form = $this->page->filter;
        $where = "1=1";
        $text = trim($form->searchkey->getText());
        $emp = $form->searchemp->getValue();
        $showdis = $form->showdis->isChecked();

        if ($emp > 0) {
            $where = $where . " and detail like '%<emp_id>{$emp}</emp_id>%' ";
        }
        if ($showdis > 0) {
            
        } else {
            $where = $where . " and disabled <> 1";
        }
        if (strlen($text) > 0) {
            $text = Equipment::qstr('%' . $text . '%');
            $where = $where . " and (eq_name like {$text} or detail like {$text} )  ";
        }
        return $where;
    }

    public function getItemCount() {
        return Equipment::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return Equipment::find($this->getWhere(), "eq_name asc", $count, $start);
    }

    public function getItem($id) {
        return Equipment::load($id);
    }

}
