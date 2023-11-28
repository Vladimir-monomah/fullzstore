<?php

namespace App\Entity;

/**
 * Класс-сущность  контрагент
 *
 * @table=customers
 * @view=customers_view
 * @keyfield=customer_id
 */
class Customer extends \ZCL\DB\Entity {

    const STATUS_ACTUAL = 0;  //актуальный
    const STATUS_DISABLED = 1; //не используется
    const STATUS_WAIT = 2; //потенциальный

    protected function init() {
        $this->customer_id = 0;
        $this->status = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->detail = "<detail><code>{$this->code}</code>";
        $this->detail .= "<discount>{$this->discount}</discount>";
        $this->detail .= "<bonus>{$this->bonus}</bonus>";
        $this->detail .= "<type>{$this->type}</type>";
        $this->detail .= "<jurid>{$this->jurid}</jurid>";
        $this->detail .= "<address><![CDATA[{$this->address}]]></address>";
        $this->detail .= "<comment><![CDATA[{$this->comment}]]></comment>";
        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->detail);

        $this->discount = doubleval($xml->discount[0]);
        $this->bonus = (int) ($xml->bonus[0]);
        $this->type = (int) ($xml->type[0]);
        $this->jurid = (int) ($xml->jurid[0]);
        $this->address = (string) ($xml->address[0]);
        $this->comment = (string) ($xml->comment[0]);

        parent::afterLoad();
    }

    public function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
         $sql = "  select count(*)  from  documents where   customer_id = {$this->customer_id}  ";
        $cnt = $conn->GetOne($sql);
        if ($cnt > 0)
            return "На  контрагента есть  ссылки  в  документах";
        return "";
    }

    /**
     * список   для комбо
     * 
     */
    public static function getList() {
        return Customer::findArray("customer_name", "status=" . Customer::STATUS_ACTUAL);
    }

}
