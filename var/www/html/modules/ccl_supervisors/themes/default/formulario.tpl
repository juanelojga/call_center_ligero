{literal}
<span us-spinner="{radius:15, width:4, length: 16, lines: 20, top: '50%', left: '50%', position: 'absolute'}" spinner-key="spinner-1"></span>
{/literal}
<table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="letra12">
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
</table>
<table class="tabForm" style="font-size: 16px;" width="100%" >
    <tr class="letra12">
        <td align="left"><b>{$usuario.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$usuario.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$nombre.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$nombre.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$grupo.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$grupo.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$MARCA_POR_ASIGNAR}: <span class="required">*</span></b></td>
        <td align="left">
            <form style='margin-bottom:0;' ng-submit='asignarMarca()' name='nuevoMarca'>
                <select ng-model="marcaPorAsignar" ng-options="m.marca as m.nombre for m in marcasPorAsignar"></select>&nbsp;&nbsp;<input class="button" type="submit" value="{$ADD}">
            </form>
        </td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$MARCAS_ASIGNADAS}: </b></td>
        <td align="left">
            <ul id="marcasAsignadas">
                <div ng-repeat="marcaAsignada in marcasAsignadas">
                    <li><button class="button" ng-click="borrarMarca(marcaAsignada.marca)"><i class="fa fa-trash"></i></button>&nbsp;&nbsp;{literal}{{marcaAsignada.nombre}}{/literal}</li>
                    <br>
                </div>
            </ul>
        </td>
    </tr>
</table>
{$id_supervisor.INPUT}