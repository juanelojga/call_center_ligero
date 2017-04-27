var app = angular.module('CdrApp', [
	'ngResource',
	'angularSpinner'
	]);

app.controller('MainCtrl', ['$scope', 'Download', 'usSpinnerService',
	function ($scope, Download, usSpinnerService) {

		$scope.numberOfParts = 0;
		$scope.total = 0;
		$scope.archivos = [];
		$scope.date_start = "";
		$scope.date_end = "";
		$scope.status = "";
		$scope.numberOfFiles = "";
		$scope.field_pattern = "";
		$scope.field_name = "";
		$scope.agente_numero_inicio = "";
		$scope.agente_numero_fin = "";
		$scope.agente_nombre = "";
		$scope.marca_prefijo = "";
		$scope.marca_nombre = "";
		$scope.campana_prefijo_inicio = "";
		$scope.campana_prefijo_fin = "";
		$scope.campana_nombre = "";
		$scope.canDownload = 0;
		$scope.extension_inicio = "";
		$scope.extension_fin = "";
		$scope.billsec_inicio = "";
		$scope.billsec_fin = "";
		$scope.tipo = "";

		$scope.calculateTotal = function () {
			if ($scope.date_start != '' && $scope.date_end != '' && $scope.status != '' && $scope.numberOfFiles != '') {
				usSpinnerService.spin('spinner-1');
				Download.get({
					action: "get_total",
					date_start: $scope.date_start,
					date_end: $scope.date_end,
					status: $scope.status,
					field_pattern: $scope.field_pattern,
					field_name: $scope.field_name,
					agente_numero_inicio: $scope.agente_numero_inicio,
					agente_numero_fin: $scope.agente_numero_fin,
					agente_nombre: $scope.agente_nombre,
					marca_prefijo: $scope.marca_prefijo,
					marca_nombre: $scope.marca_nombre,
					campana_prefijo_inicio: $scope.campana_prefijo_inicio,
					campana_prefijo_fin: $scope.campana_prefijo_fin,
					campana_nombre: $scope.campana_nombre,
					extension_inicio: $scope.extension_inicio,
					extension_fin: $scope.extension_fin,
					billsec_inicio: $scope.billsec_inicio,
					billsec_fin: $scope.billsec_fin,
					tipo: $scope.tipo
				}).$promise.then(
				function(data) {
					$scope.total = data.total;
					$scope.canDownload = 1;
					usSpinnerService.stop('spinner-1');
				}, function (error) {
					console.log(error);
					usSpinnerService.stop('spinner-1');
				});
			} 
		}

		$scope.download = function () {
			if ($scope.canDownload && $scope.date_start != '' && $scope.date_end != '' && $scope.status != '' && $scope.numberOfFiles != '') {

				usSpinnerService.spin('spinner-1');

				var parts = Math.ceil($scope.total / $scope.numberOfFiles);
				var iterations = 0;

				for (var i = 0; i < parts; i++) {
					
					usSpinnerService.spin('spinner-1');

					var offset = $scope.numberOfFiles * i;

					Download.get({
						action: "masive",
						limit: $scope.numberOfFiles,
						offset: offset,
						date_start: $scope.date_start,
						date_end: $scope.date_end,
						status: $scope.status,
						field_pattern: $scope.field_pattern,
						field_name: $scope.field_name,
						agente_numero_inicio: $scope.agente_numero_inicio,
						agente_numero_fin: $scope.agente_numero_fin,
						agente_nombre: $scope.agente_nombre,
						marca_prefijo: $scope.marca_prefijo,
						marca_nombre: $scope.marca_nombre,
						campana_prefijo_fin: $scope.campana_prefijo_fin,
						campana_nombre: $scope.campana_nombre,
						extension_inicio: $scope.extension_inicio,
						extension_fin: $scope.extension_fin,
						billsec_inicio: $scope.billsec_inicio,
						billsec_fin: $scope.billsec_fin,
						tipo: $scope.tipo
					}).$promise.then(
					function(data) {
						iterations++;
						if (data.message == "success") {
							$scope.archivos.push(data);
						}
						stopSpinner(iterations, parts);
					}, function (error) {
						console.log(error);
						usSpinnerService.stop('spinner-1');
					});

				};
			} else {
				alert("No se han seleccionado todos los parámetros obligatorios");
			}
		};

		stopSpinner = function (iterations, parts) {
			if (iterations == parts ) {
				usSpinnerService.stop('spinner-1');
			}
		}
	}]);

app.factory('Download', function ($resource) {
	return $resource('/index.php?menu=ccl_cdrreport', {
		'update': { method: 'PUT' }
	});
});