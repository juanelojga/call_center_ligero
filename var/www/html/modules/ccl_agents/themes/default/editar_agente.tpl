<table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="letra12">
        <td align="left">
            <input class="button" type="submit" name="save_edit" value="{$EDIT}">&nbsp;&nbsp;
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </td>
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
</table>
<table class="tabForm" style="font-size: 16px;" width="100%" >
    
    <tr class="letra12">
        <td align="left"><b>{$marca_nombre.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$marca_nombre.INPUT}</td>
    </tr>

    <tr class="letra12">
        <td align="left"><b>{$campana_nombre.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$campana_nombre.INPUT}</td>
    </tr>

    <tr class="letra12">
        <td align="left"><b>{$numero.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$numero.INPUT}</td>
    </tr>

    <tr class="letra12">
        <td align="left"><b>{$nombre.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$nombre.INPUT}</td>
    </tr>

</table>
<input class="button" type="hidden" name="numero_agente" value="{$NUMERO_AGENTE}" />