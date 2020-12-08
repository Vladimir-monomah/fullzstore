<?php

namespace App\Entity;

/**
 * Клас-сущность  коментари
 *
 * @table=messages
 * @view=messages_view
 * @keyfield=message_id
 */
class Message extends \ZCL\DB\Entity {

    const TYPE_DOC = 1;
    const TYPE_CUST = 2;
    const TYPE_EQ = 3;
    const TYPE_NOTE = 4;
    const TYPE_ISSUE = 5;

    protected function afterLoad() {
        $this->created = strtotime($this->created);
    }

}
