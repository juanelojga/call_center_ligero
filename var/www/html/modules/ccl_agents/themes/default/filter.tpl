<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="letra12">
    	<td width="10%">
    		<a href="{$ADD_NEW_LINK}">{$ADD_NEW}</a>
    	</td>
        <td width="10%" align="center">
        	<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
        		<tr class="letra12">
        			<td>{$filter_by_brand.LABEL}:</td>
        			<td>{$filter_by_brand.INPUT}</td>
        		</tr>
        		<tr class="letra12">
        			<td>{$filter_by_campaign.LABEL}:</td>
        			<td>{$filter_by_campaign.INPUT}</td>
        		</tr>
        	</table>
        </td>
        <td width="10%" align="center">
            <table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
                <tr class="letra12">
                    <td>{$filter_field.LABEL}:</td>
                    <td>{$filter_field.INPUT}</td>
                </tr>
                <tr class="letra12">
                    <td></td>
                    <td>{$filter_value.INPUT}</td>
                </tr>
            </table>
        </td>
        <td width="10%" align="center">
            <input class="button" type="submit" name="show" value="{$SHOW}" />
        </td>
    </tr>
</table>