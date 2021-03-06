var controllers = angular.module('Controllers', ['ngRoute']);

Object.prototype.getKeyByValue = function( value ) {
    for (var prop in this) {
        if (this.hasOwnProperty(prop)) {
            if (this[prop] === value)
                return prop;
        }
    }
};

controllers.controller('LogViewController', ['$scope', '$http', '$routeParams',
function ($scope, $http, $routeParams) {
    $scope.busy = false;
    $scope.$parent.busySearch = false;
    $scope.context = [];
    $scope.$parent.filter = {
        text: null,
        logger: null,
        level: 100
    };
    $scope.$parent.isFiltered = false;
    $scope.filterTextTimeout = null;
    $scope.init = true;
    $scope.$parent.searchInputWide = false;
    var client = $routeParams.client;
    var log = $routeParams.log;
    $scope.$parent.levels = {
        100: 'debug',
        200: 'info',
        250: 'notice',
        300: 'warning',
        400: 'error',
        500: 'critical',
        550: 'alert',
        600: 'emergency'
    };
    $scope.$parent.levelIcons = {
        100: 'bug',
        200: 'info-circle',
        250: 'file-text',
        300: 'warning',
        400: 'times-circle',
        500: 'fire',
        550: 'bell',
        600: 'flash'
    };

    $scope.getLog = function (client, log) {
        $scope.busy = true;
        $http.get('api/logs/'+client+'/'+log, { params: $scope.$parent.filter })
            .then(function successCallback(response) {
                $scope.$parent.currentLog = response.data;
                $scope.busy = false;
                $scope.$parent.busySearch = false;
                $scope.scrollTop();
            }, function errorCallback() {
                $scope.busy = false;
                $scope.$parent.busySearch = false;
            });
    };

    $scope.getMore = function () {
        $scope.busy = true;
        $http.get($scope.$parent.currentLog.next_page_url)
            .then(function successCallback(response) {
                $scope.$parent.currentLog.lines.push.apply($scope.$parent.currentLog.lines, response.data.lines);
                $scope.$parent.currentLog.next_page_url = response.data.next_page_url;
                $scope.busy = false;
            }, function() {
                $scope.busy = false;
            });
    };

    $scope.getConfig = function () {
        $http.get('api/config')
            .then(function successCallback(response) {
                $scope.config = response.data;
            });
    };

    $scope.formatDate = function(date) {
        return new Date(date);
    };

    $scope.$parent.getLevelNumber = function(level) {
        return $scope.$parent.levels.getKeyByValue(level.toLowerCase());
    };

    $scope.$parent.getLevelIcon = function(level) {
        if (level in $scope.$parent.levelIcons) return $scope.$parent.levelIcons[level];
        return null;
    };

    $scope.toggleContext = function(id) {
        $scope.$parent.currentLog.lines[id].contextToggle = !($scope.$parent.currentLog.lines[id].contextToggle);
        console.log(id+": "+$scope.$parent.currentLog.lines[id].contextToggle);
    };

    $scope.scrollTop = function () {
        document.getElementById("logtop").scrollIntoView(true);
    };

    $scope.resetFilters = function () {
        $scope.$parent.isFiltered = false;
        $scope.$parent.filter = {
            text: null,
            logger: null,
            level: 100
        };
    };

    $scope.getConfig();
    $scope.getLog(client, log);
    $scope.$watchGroup(['$parent.filter.logger', '$parent.filter.level', '$parent.filter.text'], function(nv, ov) {
        if($scope.init) {
            $scope.init = false;
        } else {
            if($scope.filterTextTimeout) { clearTimeout($scope.filterTextTimeout); }
            $scope.filterTextTimeout = setTimeout(function() {
                if($scope.$parent.filter.text != "" || $scope.$parent.filter.logger != null || $scope.$parent.filter.level > 100) {
                    $scope.$parent.isFiltered = true;
                }
                $scope.$parent.busySearch = true;
                $scope.getLog(client, log);
            },300);
        }
    });
    $scope.$on('$locationChangeStart', function(event) {
        $scope.$parent.currentLog = null;
        $scope.resetFilters();
    });
}]);

controllers.controller('NavigationController', ['$scope', '$http', '$routeParams',
    function ($scope, $http, $routeParams) {
        $scope.route = $routeParams;

        $scope.clearCache = function () {
            $http.get('api/cache/clear')
                .then(function successCallback() {
                    alert("Cache cleared");
                });
        };

        $http.get('api/logs?logs=1')
            .success(function (data) {
                $scope.clients = data.clients;
            });
    }]);