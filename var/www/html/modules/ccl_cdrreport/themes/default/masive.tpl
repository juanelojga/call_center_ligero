<div ng-app='CdrApp' ng-controller='MainCtrl'>

	{literal}
	<span us-spinner="{radius:15, width:4, length: 16, lines: 20, top: '50%', left: '50%', position: 'absolute'}" spinner-key="spinner-1"></span>
	{/literal}

	<table class="tabForm" style="font-size: 16px;" width="100%" >
		
		<tr class="letra12">
			<td align="right" width="20%">
				<button class="button" ng-click="download()">{$SAVE}</button>
			</td>
			<td align="left">
				<a href="/index.php?menu=ccl_cdrreport" class="button">{$CANCEL}</a>
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$FECHA_INICIO_LABEL}: <span  class="required">*</span></b></td>
			<td align="left" nowrap width="160px"><input type="text" name="date_start" value="" style="width: 10em; color: #840; background-color: #fafafa; border: 1px solid #999999; text-align: center" ng-model="date_start" ng-change="calculateTotal()"/>
				{literal}
				<script type="text/javascript">
					$(function() {
						$("input[name=date_start]").datepicker({"showOn":"button","firstDay":1,"buttonImage":"images\/calendar.gif","buttonImageOnly":true,"dateFormat":"yy-mm-dd","timeFormat":"HH:mm","changeMonth":true,"changeYear":true,"showWeek":true,"constrainInput":true});
					});
				</script>
				{/literal}
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$FECHA_FIN_LABEL}: <span  class="required">*</span></b></td>
			<td align="left" nowrap width="160px"><input type="text" name="date_end" value="" style="width: 10em; color: #840; background-color: #fafafa; border: 1px solid #999999; text-align: center" ng-model="date_end" ng-change="calculateTotal()"/>
				{literal}
				<script type="text/javascript">
					$(function() {
						$("input[name=date_end]").datepicker({"showOn":"button","firstDay":1,"buttonImage":"images\/calendar.gif","buttonImageOnly":true,"dateFormat":"yy-mm-dd","timeFormat":"HH:mm","changeMonth":true,"changeYear":true,"showWeek":true,"constrainInput":true});
					});
				</script>
				{/literal}
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$STATUS_LABEL}: <span  class="required">*</span></b></td>
			<td align="left">
				<select ng-model="status" ng-change="calculateTotal()">
					<option value="ANSWERED">ANSWERED</option>
				</select>
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$FIELD_NAME}: </b></td>
			<td align="left">
				<select ng-model="field_name" ng-change="calculateTotal()">
					<option value="dst">Destino</option>
					<option value="channel">Canal origen</option>	
					<option value="dstchannel">Canal destino</option>
				</select>
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"></td>
			<td align="left">
				<input type="text" ng-model="field_pattern" ng-change="calculateTotal()">
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$EXTENSION}: </b></td>
			<td align="left">
				<input type="text" ng-model="extension_inicio" ng-change="calculateTotal()" style="width:65px"> - <input type="text" ng-model="extension_fin" ng-change="calculateTotal()" style="width:65px">
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$AGENTE_NUMERO}: </b></td>
			<td align="left">
				<input type="text" ng-model="agente_numero_inicio" ng-change="calculateTotal()" style="width:65px"> - <input type="text" ng-model="agente_numero_fin" ng-change="calculateTotal()" style="width:65px">
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$AGENTE_NOMBRE}: </b></td>
			<td align="left">
				<input type="text" ng-model="agente_nombre" ng-change="calculateTotal()">
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$CAMPANA_PREFIJO}: </b></td>
			<td align="left">
				<input type="text" ng-model="campana_prefijo_inicio" ng-change="calculateTotal()" style="width:65px"> - <input type="text" ng-model="campana_prefijo_fin" ng-change="calculateTotal()" style="width:65px">
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$CAMPANA_NOMBRE}: </b></td>
			<td align="left">
				<input type="text" ng-model="campana_nombre" ng-change="calculateTotal()">
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$MARCA_PREFIJO}: </b></td>
			<td align="left">
				<input type="text" ng-model="marca_prefijo" ng-change="calculateTotal()">
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$MARCA_NOMBRE}: </b></td>
			<td align="left">
				<input type="text" ng-model="marca_nombre" ng-change="calculateTotal()">
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$TIPO}: </b></td>
			<td align="left">
				<select ng-model="tipo" ng-change="calculateTotal()">
					<option value="ALL">TODOS</option>
					<option value="active">Activo</option>	
					<option value="break">En descanso</option>
				</select>
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$DURACION}: </b></td>
			<td align="left">
				<input type="text" ng-model="billsec_inicio" ng-change="calculateTotal()" style="width:65px"> - <input type="text" ng-model="billsec_fin" ng-change="calculateTotal()" style="width:65px">
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$PARTS_LABEL}: <span  class="required">*</span></b></td>
			<td align="left">
				<select ng-model="numberOfFiles" ng-change="calculateTotal()">
					<option value="250">250</option>
					<option value="300">300</option>
					<option value="350">350</option>
					<option value="400">400</option>
					<option value="450">450</option>
					<option value="500">500</option>
				</select>
			</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$TOTAL}: </b></td>
			<td align="left">{literal}{{total}}{/literal}</td>
		</tr>

		<tr class="letra12">
			<td align="right" width="20%"><b>{$FILES}: </b></td>
			<td align="left">
				<div ng-repeat="archivo in archivos">
					<a href="{literal}{{archivo.url}}{/literal}">{literal}{{archivo.file}}{/literal}</a>
				</div>
			</td>
		</tr>

	</table>
</div>
