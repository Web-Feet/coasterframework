var router = {

    namedRoutes : {},
    baseUrl : {},

    addRoutes: function (importRoutes) {
        for (var importRouteName in importRoutes) {
            if(!importRoutes.hasOwnProperty(importRouteName)) continue;
            this.namedRoutes[importRouteName] = importRoutes[importRouteName];
        }
    },

    setBase: function (baseUrl) {
        this.baseUrl = baseUrl.replace(/\/+$/, '') + '/';
    },

    route: function (routeName, params) {
        if (this.namedRoutes[routeName]) {
            var uri = this.namedRoutes[routeName];
            for (var paramName in params) {
                if(!params.hasOwnProperty(paramName)) continue;
                uri = uri.replace(new RegExp('\{'+paramName+'\\?*\}', 'g'), params[paramName]);
            }
            return this.baseUrl + uri.replace(/\{\w+\?}/g, '').replace(/\/+/, '/').replace(/\/+$/, '');
        }
    }

};

function route(routeName, params) {
    return router.route(routeName, params);
}