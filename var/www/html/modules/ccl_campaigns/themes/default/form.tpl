<table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="letra12">
        {if $mode eq 'input'}
        <td align="left">
            <input class="button" type="submit" name="save_new" value="{$SAVE}">&nbsp;&nbsp;
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </td>
        {elseif $mode eq 'view'}
        <td align="left">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </td>
        {elseif $mode eq 'edit'}
        <td align="left">
            <input class="button" type="submit" name="save_edit" value="{$EDIT}">&nbsp;&nbsp;
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </td>
        {/if}
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
</table>
<table class="tabForm" style="font-size: 16px;" width="100%" >
    <tr class="letra12">
        <td align="left"><b>{$prefijo.LABEL}: <span  class="required">*</span></b></td>
        {if $mode eq 'input'}
            <td align="left">{$prefijo.INPUT}</td>
        {elseif $mode eq 'edit'}
            <td align="left">{$PREFIX}</td>
        {/if}
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$marca.LABEL}: <span  class="required">*</span></b></td>
        {if $mode eq 'input'}
            <td align="left">{$marca.INPUT}</td>
        {elseif $mode eq 'edit'}
            <td align="left">{$BRAND}</td>
        {/if}
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$nombre.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$nombre.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$descripcion.LABEL}: </b></td>
        <td align="left">{$descripcion.INPUT}</td>
    </tr>
</table>
<input class="button" type="hidden" name="prefix" value="{$PREFIX}" />
<input class="button" type="hidden" name="brand" value="{$BRAND}" />