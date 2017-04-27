<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
	<tr class="letra12">
		<p align="center">{$Masive}</p>
	</tr>
	<tr class="letra12">
		<td width="10%" align="center">
			<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
				<tr class="letra12">
					<td>{$date_start.LABEL}:</td>
					<td>{$date_start.INPUT}</td>
				</tr>
				<tr class="letra12">
					<td>{$date_end.LABEL}:</td>
					<td>{$date_end.INPUT}</td>
				</tr>
				<tr class="letra12">
					<td>{$field_name.LABEL}:</td>
					<td>{$field_name.INPUT}</td>
				</tr>
				<tr class="letra12">
					<td></td>
					<td>{$field_pattern.INPUT}</td>
				</tr>
			</table>
		</td>
		<td width="10%" align="center">
			<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
				<tr class="letra12">
					<td>{$marca_prefijo.LABEL}:</td>
					<td>{$marca_prefijo.INPUT}</td>
				</tr>
				<tr class="letra12">
					<td>{$marca_nombre.LABEL}:</td>
					<td>{$marca_nombre.INPUT}</td>
				</tr>
				<tr class="letra12">
					<td>{$campana_prefijo_inicio.LABEL}:</td>
					<td>{$campana_prefijo_inicio.INPUT} - {$campana_prefijo_fin.INPUT}</td>
				</tr>
				<tr class="letra12">
					<td>{$campana_nombre.LABEL}:</td>
					<td>{$campana_nombre.INPUT}</td>
				</tr>
				<tr class="letra12">
					<td>{$billsec_inicio.LABEL}:</td>
					<td>{$billsec_inicio.INPUT} - {$billsec_fin.INPUT}</td>
				</tr>
			</table>
		</td>
		<td width="10%" align="center">
			<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
				<tr class="letra12">
					<td>{$extension_inicio.LABEL}:</td>
					<td>{$extension_inicio.INPUT} - {$extension_fin.INPUT}</td>
				</tr>
				<tr class="letra12">
					<td>{$agente_numero_inicio.LABEL}:</td>
					<td>{$agente_numero_inicio.INPUT} - {$agente_numero_fin.INPUT}</td>
				</tr>
				<tr class="letra12">
					<td>{$agente_nombre.LABEL}:</td>
					<td>{$agente_nombre.INPUT}</td>
				</tr>
				<tr class="letra12">
					<td>{$status.LABEL}:</td>
					<td>{$status.INPUT}</td>
				</tr>
				<tr class="letra12">
					<td>{$tipo.LABEL}:</td>
					<td>{$tipo.INPUT}</td>
				</tr>
			</table>
		</td>
		<td width="10%" align="center">
			<input class="button" type="submit" name="show" value="{$Filter}" />
		</td>
	</tr>
</table>