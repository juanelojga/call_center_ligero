var app = angular.module('app', [
	'ngResource',
	'angularSpinner'
	]);

app.controller('MainCtrl', ['$scope', 'Agentes', 'usSpinnerService', '$window',
	function ($scope, Agentes, usSpinnerService, $window) {

		$scope.agentesLibres = {
			total: 0
		};

		$scope.guardarAgente  = function() {
			var cantidad = angular.element('#amount').val();
			
			if (cantidad > $scope.agentesLibres.total || $scope.agente == "" || $scope.nombre == "" || $scope.marca == "" || $scope.campana == "") {
				alert("No pueden crear los agentes");
			} else {
				usSpinnerService.spin('spinner-1');
				Agentes.get({
					action: "crear_agentes",
					campana: $scope.campana,
					semilla_agente: $scope.agente,
					semilla_nombre: $scope.nombre,
					cantidad: cantidad
				}).$promise.then(
				function(data) {
					alert("Se crearon: " + data.total + " agentes.");
					usSpinnerService.stop('spinner-1');
					$window.location.href = '/index.php?menu=ccl_agents';
				}, function (error) {
					console.log(error);
					usSpinnerService.stop('spinner-1');
				});
			}
		}

		$scope.obtenerMarcas = function() {
			usSpinnerService.spin('spinner-1');
			Agentes.query({
				action: "obtener_marcas"
			}).$promise.then(
			function(data) {
				$scope.marcas = data;
				$scope.campanas = [];
				$scope.agente = "";
				$scope.nombre = "";
				$scope.agentesLibres.total = 0;
				usSpinnerService.stop('spinner-1');
			}, function (error) {
				console.log(error);
				usSpinnerService.stop('spinner-1');
			});
		}

		$scope.obtenerCampanas = function() {
			usSpinnerService.spin('spinner-1');
			Agentes.query({
				action: "obtener_campanas",
				marca: $scope.marca
			}).$promise.then(
			function(data) {
				$scope.campanas = data;
				$scope.agente = "";
				$scope.nombre = "";
				$scope.agentesLibres.total = 0;
				usSpinnerService.stop('spinner-1');
			}, function (error) {
				console.log(error);
				usSpinnerService.stop('spinner-1');
			});
		}

		$scope.obtenerAgentesLibres = function() {
			Agentes.get({
				action: "obtener_agentes_libres",
				campana: $scope.campana
			}).$promise.then(
			function(data) {
				$scope.agentesLibres.total = data.total;
				$scope.agentesLibres.agentes = data.agentes;
				$scope.agente = "";
				$scope.nombre = "";
				usSpinnerService.stop('spinner-1');
			}, function (error) {
				console.log(error);
				usSpinnerService.stop('spinner-1');
			});
		}

		$scope.seleccionarAgente = function() {
			$scope.nombre = "";
		}

	}]);

app.factory('Agentes', function ($resource) {
	return $resource('/index.php?menu=ccl_agents', {
		'update': { method: 'PUT' }
	});
});

app.filter('digits', function() {
return function(input) {
   if (input < 10) { 
          input = '0' + input;
      }

      return input;
    }
});