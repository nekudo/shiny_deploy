(function() {
    'use strict';

    angular
        .module('shinyDeploy')
        .factory('auth', auth);

    auth.$inject = ['ws', '$rootScope', '$location', 'jwtHelper'];

    /* @ngInject */
    function auth(ws, $rootScope, $location, jwtHelper) {

        var service = {
            init: init,
            login: login,
            setToken: setToken
        };

        return service;

        var isAuthenticated = false;

        function init() {
            $rootScope.$on('$locationChangeStart', function(event, newUrl, oldUrl) {
                checkAuth();
            });

            ws.addListener('unauthorized', function(eventData) {
                handleUnauthorized(eventData);
            });
        }

        function checkAuth() {
            if (!validateToken()) {
                $location.path('/login');
                return false;
            }
            isAuthenticated = true;
            return true;
        }

        function handleUnauthorized(eventData) {
            sessionStorage.removeItem('token');
            $location.path('/login');
            return false;
        }

        /**
         * Validates the JWT.
         *
         * @returns {Boolean}
         */
        function validateToken() {

            // check if token exists:
            var token = getToken();
            if (token === null) {
                $location.path('/login');
                return false;
            }

            // check if token is expired:
            if (jwtHelper.isTokenExpired(token)) {
                $location.path('/login');
                return false;
            }

            // check client-id and issuer:
            var clientId = sessionStorage.getItem('uuid');
            var tokenDecoded = jwtHelper.decodeToken(token);
            if (!tokenDecoded.hasOwnProperty('iss') || tokenDecoded.iss !== 'ShinyDeploy') {
                return false;
            }
            if (!tokenDecoded.hasOwnProperty('jti') || tokenDecoded.jti !== clientId) {
                return false;
            }

            return true;
        }

        function login(password) {
            var requestParams = {
                password: password
            };
            return ws.sendDataRequest('login', requestParams);
        }

        function setToken(token) {
            sessionStorage.setItem('token', token);
            ws.setToken(token);
        }

        function getToken() {
            return sessionStorage.getItem('token');
        }
    }
})();
