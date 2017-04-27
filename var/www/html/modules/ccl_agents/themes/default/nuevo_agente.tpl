{literal}
<span us-spinner="{radius:15, width:4, length: 16, lines: 20, top: '50%', left: '50%', position: 'absolute'}" spinner-key="spinner-1"></span>
{/literal}
<table class="tabForm" style="font-size: 16px;" width="100%">
	<tr>
		<td align="center" width="25%">
			<input class="button" type="submit" value="{$SAVE}">
		</td>
		<td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
	</tr>
	<tr>
		<td align="center" width="25%">
			<table class="tabForm" style="font-size: 16px;" width="100%">
				<tr class="letra12">
					<td align="right"><b>{$QUANTITY}: <span class="required">*</span></b></td>
					<td align="left"><input type="text" id="amount" readonly style="border:0; font-weight:bold; width: 50px;" align="center"></td>
				</tr>
			</table>
			<div id="slider-vertical" style="height:200px;"></div>
		</td>
		<td align="left">
			<table class="tabForm" style="font-size: 16px;" width="100%">
				<tr class="letra12">
					<td align="left"><b>{$BRAND}: <span class="required">*</span></b></td>
					<td align="left"><select ng-model="marca" ng-options="m.prefijo as m.nombre for m in marcas" ng-change="obtenerCampanas()"></select></td>
				</tr>

				<tr class="letra12">
					<td align="left"><b>{$CAMPAIGN}: <span class="required">*</span></b></td>
					<td align="left"><select ng-model="campana" ng-options="c.prefijo as c.nombre for c in campanas" ng-change="obtenerAgentesLibres()"></select></td>
				</tr>

				<tr class="letra12">
					<td align="left"><b>{$AGENT_PREFIX}: <span class="required">*</span></b> ({literal}{{agentesLibres.total}}{/literal} {$FREE_AGENTS})</td>
					<td align="left">
						<select ng-model="agente" ng-change="seleccionarAgente()">
							{literal}
							<option ng-repeat="a in agentesLibres.agentes" value="{{a}}">{{a|digits}}</option>
							{/literal}
						</select>
					</td>
				</tr>

				<tr class="letra12">
					<td align="left"><b>{$nombre.LABEL}: <span class="required">*</span></b> (ex: {literal}{{nombre}}{{agente|digits}}{/literal})</td>
					<td align="left">{$nombre.INPUT}</td>
				</tr>

			</table>
		</td>
	</tr>
</table>