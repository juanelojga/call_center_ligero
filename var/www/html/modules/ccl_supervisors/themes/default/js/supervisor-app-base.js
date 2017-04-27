var app = angular.module('SupervisorApp', [
	'ngResource',
	'angularSpinner'
	]);

app.controller('MainCtrl', ['$scope', 'Supervisor', 'usSpinnerService',
	function ($scope, Supervisor, usSpinnerService) {

		$scope.obtenerInformacionSupervisor = function(id_user) {
			obtenerInformacionSupervisor(id_user);
		}

		$scope.asignarMarca = function() {
			usSpinnerService.spin('spinner-1');
			Supervisor.get({
				action: "asignar_marca",
				id_supervisor: $scope.id_supervisor,
				marca: $scope.marcaPorAsignar
			}).$promise.then(
			function(data) {
				if(data.estado == "success") {
					obtenerInformacionSupervisor($scope.id_supervisor);
				}
				usSpinnerService.stop('spinner-1');
			}, function (error) {
				console.log(error);
				usSpinnerService.stop('spinner-1');
			});
		}

		$scope.borrarMarca = function(marca) {
			usSpinnerService.spin('spinner-1');
			Supervisor.get({
				action: "borrar_marca",
				id_supervisor: $scope.id_supervisor,
				marca: marca
			}).$promise.then(
			function(data) {
				if(data.estado == "success") {
					obtenerInformacionSupervisor($scope.id_supervisor);
				}
				usSpinnerService.stop('spinner-1');
			}, function (error) {
				console.log(error);
				usSpinnerService.stop('spinner-1');
			});
		}

		obtenerInformacionSupervisor = function(id_user)
		{
			usSpinnerService.spin('spinner-1');
			Supervisor.get({
				action: "obtener_informacion_supervisor",
				id_supervisor: id_user
			}).$promise.then(
			function(data) {
				if(data.estado == "success") {
					$scope.marcasAsignadas = data.marcasAsignadas;
					$scope.marcasPorAsignar = data.marcasPorAsignar;
					$scope.id_supervisor = id_user;
				}
				usSpinnerService.stop('spinner-1');
			}, function (error) {
				console.log(error);
				usSpinnerService.stop('spinner-1');
			});
		}

	}]);

app.factory('Supervisor', function ($resource) {
	return $resource('/index.php?menu=ccl_supervisors', {
		'update': { method: 'PUT' }
	});
});