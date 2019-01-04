(function (angular, undefined) {
    var module=angular.module('ampize.wpConnector', ['ngMaterial', 'ngMessages']);
    module.controller("WPListController",["$scope","$http","$mdToast","$mdDialog",function($scope,$http,$mdToast,$mdDialog){
        var me=this;
        me.connectors=[];
        me.isLoading=false;
        me.getNameBlacklist=function(){
            var nameBlacklist="";
            angular.forEach(me.connectors,function(connector){
                if(nameBlacklist!=""){
                    nameBlacklist=nameBlacklist+",";
                }
                nameBlacklist=nameBlacklist+connector.name;
            });
            return nameBlacklist;
        };
        me.refreshConnectors=function(){
            me.isLoading=true;
            $http.get('/api/admin/graphql?query=query q{wpConnectors(limit:10000){name host}}').then(
                function(response){
                    me.connectors=response.data.data.wpConnectors;
                    me.isLoading=false;
                }
            );
        };
        me.isAuthorized=false;
        $http.get("/api/admin/nr/is-authorized").then(
            function(response){
                if (response.data.success) {
                    me.isAuthorized=true;
                    me.refreshConnectors();
                }
            }
        );
        me.launchEditor=function(connectorName){
            $mdDialog.show({
                templateUrl: '/resource/wp/templates/new-wp.html',
                controller:"NewWPSourceController",
                controllerAs:"NWPSCtrl",
                fullscreen:true,
                onRemoving:function(){
                    me.refreshConnectors();
                },
                locals:{
                    connectorName:connectorName,
                    nameBlacklist:me.getNameBlacklist()
                }
            });
        };
        window.launchWPAdd=function(){
            me.launchEditor();
        };
        me.deleteConnector=function(restConnector,index,ev){
            var confirm = $mdDialog.confirm()
                .title('Are you sure you want to delete the WP connector "'+restConnector.name+'" ?')
                .textContent('This action cannot be reversed')
                .targetEvent(ev)
                .ok('Delete')
                .cancel('Cancel');
            $mdDialog.show(confirm).then(function() {
                me.connectors[index].interfaceIsLoading=true;
                $http.get('/api/admin/graphql?query=mutation m{deleteWPConnector(name: "'+restConnector.name+'") {name}}').then(
                    function(response){
                        me.refreshConnectors();
                        $mdToast.show($mdToast.simple().textContent('WP connector deleted').position('top right'));
                    }
                );
            }, function() {
            });
        };
    }]);

    module.controller("WPAddController",["$scope","$http","$mdDialog",function($scope,$http,$mdDialog){
        var me=this;
        me.launchAdd=function(){
            window.launchWPAdd();
        };
        me.isAuthorized=false;
        $http.get("/api/admin/nr/is-authorized").then(
            function(response){
                if (response.data.success) {
                    me.isAuthorized=true;
                }
            }
        );
    }]);

    module.controller("NewWPSourceController",["$scope","$http","$mdToast","$mdDialog","connectorName","nameBlacklist",function($scope,$http,$mdToast,$mdDialog,connectorName,nameBlacklist){
        var me=this;
        me.closeEditor=function(){
            $mdDialog.hide();
        };
        $scope.nameBlacklist=nameBlacklist;
        me.currentStage=1;
        me.stepForward=function(){
            me.currentStage=me.currentStage+1;
        };
        me.stepBack=function(){
            me.currentStage=me.currentStage-1;
        };
        if(connectorName&&connectorName!=""){
            me.isEdit=true;
            me.connectorName=connectorName;
            $http.get('/api/admin/graphql?query=query q{wpConnector (name:"' + me.connectorName + '"){name jsonModel host useCache defaultCacheTTL}}').then(
                function (response) {
                  console.log(response.data);
                    var newData=response.data.data.wpConnector;
                    if(newData.jsonModel){
                        newData.jsonModel = newData.jsonModel.replace(/'/g, '"');
                        newData.jsonModel=JSON.parse(newData.jsonModel);
                        newData.jsonModel=JSON.stringify(newData.jsonModel, null, 4);
                    }
                    me.newForm=newData;
                },
                function (response) {
                    console.log(response);
                }
            );
        }
        me.hasCacheStage=false;
        me.newForm={
            useCache:false

        };
        if(installedFeatures.redisCache){
            me.hasCacheStage=true;
            me.newForm.useCache=true;
            me.newForm.defaultCacheTTL=120;
        }
        me.jsonIsValid=function(){
            if (!me.newForm.jsonModel||me.newForm.jsonModel==""){
                return false
            }
            try {
                JSON.parse(me.newForm.jsonModel);
            } catch (e) {
                return false;
            }
            return true;
        };
        $scope.notifyFileChange=function(){
            var reader = new FileReader();
            reader.onload = function(e) {
                me.newForm.jsonModel=angular.copy(this.result);
                $scope.$apply();
            };
            reader.readAsText(me.jsonModelFile);

        };
        me.runIntrospect=function(){
            $http.get("/api/admin/wp/introspect",{
                params:{
                    host:me.newForm.host,
                    name:me.newForm.name
                }
            }).then(
                function(response){
                    me.newForm.jsonModel=JSON.stringify(response.data, null, 4);
                }
            );
        };
        me.submitNewAPI=function(){
            if(me.jsonIsValid()) {
                var data = angular.copy(me.newForm);
                data.jsonModel = data.jsonModel.replace(/\r?\n|\r/g, "");
                data.jsonModel = data.jsonModel.replace(/"/g, "'");
                data.jsonModel = data.jsonModel.replace(/  /g, "");
                var argsString = '(';
                var hasFirst = false;
                angular.forEach(data, function (value, key) {
                    if (hasFirst) {
                        argsString = argsString + ', '
                    } else {
                        hasFirst = true;
                    }
                    argsString = argsString + key + ':';
                    if (value == null || value == ""){
                        argsString = argsString + "null";
                    }else if (typeof(value) == "string") {
                        argsString = argsString + '"' + value + '"';
                    } else {
                        argsString = argsString + JSON.stringify(value);
                    }
                });
                me.isLoading = true;
                argsString = argsString + ')';
                argsString = argsString.replace(/"property"/g, 'property');
                argsString = argsString.replace(/"value"/g, 'value');
                argsString = argsString.replace(/"none"/g, 'none');
                argsString = argsString.replace(/"basic"/g, 'basic');
                argsString = argsString.replace(/"customParams"/g, 'customParams');
                argsString = argsString.replace(/"customHeaders"/g, 'customHeaders');
                if(me.isEdit){
                    $http.post('/api/admin/graphql',{
                        'query':'mutation m{updateWPConnector' + argsString + ' {name}}'
                    }).then(
                        function (response) {
                            $mdDialog.hide();
                            $mdToast.show($mdToast.simple().textContent('Data source updated').position('top right'));

                        },
                        function (response) {
                            me.isLoading = false;
                            console.log(response);
                        }
                    );
                } else {
                    $http.post('/api/admin/graphql',{
                        'query':'mutation m{createWPConnector' + argsString + ' {name}}'
                    }).then(
                        function (response) {
                            $mdDialog.hide();
                            $mdToast.show($mdToast.simple().textContent('Data source created').position('top right'));

                        },
                        function (response) {
                            me.isLoading = false;
                            console.log(response);
                        }
                    );
                }

            }
        };
    }]);
})(angular);
