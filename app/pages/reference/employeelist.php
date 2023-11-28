<?php

namespace App\Pages\Reference;

use ZCL\DB\EntityDataSource as EDS;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \App\Entity\Employee;

class EmployeeList extends \App\Pages\Base {

    private $_employee;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('EmployeeList'))
            return;

        $this->add(new Panel('employeetable'))->setVisible(true);
        $this->employeetable->add(new DataView('employeelist', new EDS('\App\Entity\Employee', '', 'disabled, emp_name'), $this, 'employeelistOnRow'))->Reload();
        $this->employeetable->employeelist->setPageSize(25);
        $this->employeetable->add(new \Zippy\Html\DataList\Paginator('pag', $this->employeetable->employeelist));




        $this->employeetable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('employeedetail'))->setVisible(false);

        $this->employeedetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->employeedetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
        $this->employeedetail->add(new TextInput('editlogin'));
        $this->employeedetail->add(new TextInput('editemp_name'));

        $this->employeedetail->add(new TextInput('editemail'));
        $this->employeedetail->add(new TextArea('editcomment'));
        $this->employeedetail->add(new CheckBox('editdisabled'));
        //   $this->employeedetail->add(new TextInput('editzhour',0));        
        //   $this->employeedetail->add(new TextInput('editzmon',0));        
        //   $this->employeedetail->add(new TextInput('editadvance',0));        
        //   $this->employeedetail->add(new DropDownChoice('editztype',array(1=>'Ставка',2=>'Почасовая',3=>'Сдельная'),1))->onChange($this,"onType");
        //  $this->onType($this->employeedetail->editztype);
    }

    /*
      public function onType($sender){
      $t = $sender->getValue();
      $this->employeedetail->editzmon->setVisible($t==1) ;
      $this->employeedetail->editzhour->setVisible($t==2) ;
      }
     */

    public function employeelistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('emp_name', $item->emp_name));
        $row->add(new Label('login', $item->login));
        //  $row->add(new Label('balance', $item->balance));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('EmployeeList'))
            return;

        Employee::delete($sender->owner->getDataItem()->employee_id);
        $this->employeetable->employeelist->Reload();
    }

    public function editOnClick($sender) {
        $this->_employee = $sender->owner->getDataItem();
        $this->employeetable->setVisible(false);
        $this->employeedetail->setVisible(true);
        $this->employeedetail->editlogin->setText($this->_employee->login);
        $this->employeedetail->editemp_name->setText($this->_employee->emp_name);
        $this->employeedetail->editcomment->setText($this->_employee->comment);
        $this->employeedetail->editemail->setText($this->_employee->email);
        //    $this->employeedetail->editztype->setValue($this->_employee->ztype);
        //     $this->employeedetail->editzhour->setText($this->_employee->zhour);
        //    $this->employeedetail->editzmon->setText($this->_employee->zmon);
        //     $this->employeedetail->editadvance->setText($this->_employee->advance);
        $this->employeedetail->editdisabled->setChecked($this->_employee->disabled);
    }

    public function addOnClick($sender) {
        $this->employeetable->setVisible(false);
        $this->employeedetail->setVisible(true);
        // Очищаем  форму
        $this->employeedetail->clean();


        $this->_employee = new Employee();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('EmployeeList'))
            return;

        $login = trim($this->employeedetail->editlogin->getText());
        if (strlen($login) > 0) {
            if ($login == "admin") {
                $this->setError('Недопустимый логин');
                return;
            }
            $_emp = Employee::getFirst("login = '{$login}'");
            if ($_emp != null && $_emp->employee_id != $this->_employee->employee_id) {
                $this->setError('Логин уже назначен  ' . $_emp->emp_name());
                return;
            }
            $user = \App\Entity\User::getByLogin($login);
            if ($user == null) {
                $this->setError('Несуществующий логин');
                return;
            }
        }
        $this->_employee->login = $login;
        $this->_employee->emp_name = $this->employeedetail->editemp_name->getText();
        $this->_employee->email = $this->employeedetail->editemail->getText();
        $this->_employee->comment = $this->employeedetail->editcomment->getText();
        //    $this->_employee->ztype = $this->employeedetail->editztype->getValue();
        //    $this->_employee->advance = $this->employeedetail->editadvance->getText();
        //    $this->_employee->zmon = $this->employeedetail->editzmon->getText();
        //    $this->_employee->zhour = $this->employeedetail->editzhour->getText();
        $this->_employee->disabled = $this->employeedetail->editdisabled->isChecked() ? 1 : 0;

        $this->_employee->Save();

        $this->employeedetail->setVisible(false);
        $this->employeetable->setVisible(true);
        $this->employeetable->employeelist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->employeetable->setVisible(true);
        $this->employeedetail->setVisible(false);
    }

}
