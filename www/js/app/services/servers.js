app.service('serversService', function () {
    var servers = [
        {
            id: 1,
            name: 'foo'
        },
        {
            id: 2,
            name: 'bar'
        }
    ];

    this.getServers = function () {
        return servers;
    };
});