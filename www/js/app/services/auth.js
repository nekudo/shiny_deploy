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

        /**
         * Init JTW authentication and register event listener.
         *
         */
        function init() {

            // reset token from session storeage to ws service:
            var token = getToken();
            if (token !== null) {
                ws.setToken(token);
            }

            // client-side token validation on location changes:
            $rootScope.$on('$locationChangeStart', function(event, newUrl, oldUrl) {
                checkAuth();
            });

            // Handle "unauthorized" responses from server:
            ws.addListener('unauthorized', function(eventData) {
                handleUnauthorized(eventData);
            });
        }

        /**
         * Redirects to login page if token is invalid or expired.
         *
         * @returns {Boolean}
         */
        function checkAuth() {
            if (!validateToken()) {
                $location.path('/login');
                return false;
            }
            return true;
        }

        /**
         * Redirects to login page on "unauthorized" events received by WS server.
         *
         * @param {Object} eventData
         * @returns {Boolean}
         */
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
                return false;
            }

            // check if token is expired:
            if (jwtHelper.isTokenExpired(token)) {
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

        /**
         * Sends login request to WS server.
         *
         * @param {type} password
         * @returns {Promise}
         */
        function login(password) {
            var requestParams = {
                password: password
            };
            return ws.sendDataRequest('login', requestParams);
        }

        /**
         * Sets JWT to session-storage and WS service.
         *
         * @param {String} token
         */
        function setToken(token) {
            sessionStorage.setItem('token', token);
            ws.setToken(token);
        }

        /**
         * Fetches JWT from session storage.
         *
         * @returns {String|null}
         */
        function getToken() {
            return sessionStorage.getItem('token');
        }
    }
})();
