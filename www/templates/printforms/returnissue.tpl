 
<table class="ctable" border="0" cellspacing="0" cellpadding="2">



    <tr>
        <td></td>
        <td>От покупателя</td>
        <td colspan="5">{{customername}}</td>
    </tr>

    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="7" valign="middle">
            <br><br> Возврат № {{document_number}} от {{date}} <br><br><br>
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th colspan="2"   style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Наименование</th>

        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Кол.</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Цена</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="80">Сумма</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td colspan="2">{{tovar_name}}</td>

        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="5" align="right">Итого:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>

    <tr>
        <td></td>
        <td colspan="2">
            Отправил
        </td>
        <td colspan="4">
            Получил
        </td>

    </tr>
</table>

