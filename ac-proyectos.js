(function () {
    'use strict';

    var scripts = document.getElementsByTagName("script");
    var currentScriptPath = scripts[scripts.length - 1].src;

    angular.module('acProyectos', [])
        .factory('ProyectService', ProyectService)
        .service('ProyectVars', ProyectVars)
        .factory('DonationService', DonationService)
        .service('DonationVars', DonationVars)
    ;


    ProyectService.$inject = ['$http', 'ProyectVars', '$cacheFactory', 'AcUtils'];
    function ProyectService($http, ProyectVars, $cacheFactory, AcUtils) {
        //Variables
        var service = {};

        var url = currentScriptPath.replace('ac-proyectos.js', '/includes/ac-proyectos.php');

        //Function declarations
        service.get = get;
        service.getByParams = getByParams;
        service.getMasVendidos = getMasVendidos;
        service.getByCategoria = getByCategoria;

        service.create = create;

        service.update = update;

        service.remove = remove;


        service.goToPagina = goToPagina;
        service.next = next;
        service.prev = prev;

        return service;

        //Functions
        /**
         * @description Obtiene todos los proyectos
         * @param callback
         * @returns {*}
         */
        function get(callback) {
            var urlGet = url + '?function=getProyectos';
            var $httpDefaultCache = $cacheFactory.get('$http');
            var cachedData = [];


            // Verifica si existe el cache de proyectos
            if ($httpDefaultCache.get(urlGet) != undefined) {
                if (ProyectVars.clearCache) {
                    $httpDefaultCache.remove(urlGet);
                }
                else {
                    cachedData = $httpDefaultCache.get(urlGet);
                    callback(cachedData);
                    return;
                }
            }


            return $http.get(urlGet, {cache: true})
                .success(function (data) {
                    $httpDefaultCache.put(urlGet, data);
                    ProyectVars.clearCache = false;
                    ProyectVars.paginas = (data.length % ProyectVars.paginacion == 0) ? parseInt(data.length / ProyectVars.paginacion) : parseInt(data.length / ProyectVars.paginacion) + 1;
                    callback(data);
                })
                .error(function (data) {
                    callback(data);
                    ProyectVars.clearCache = false;
                })
        }


        /**
         * @description Retorna la lista filtrada de proyectos
         * @param param -> String, separado por comas (,) que contiene la lista de par�metros de b�squeda, por ej: nombre, sku
         * @param value
         * @param callback
         */
        function getByParams(params, values, exact_match, callback) {
            get(function (data) {
                AcUtils.getByParams(params, values, exact_match, data, callback);
            })
        }

        /**
         * @description Retorna los primero 8 proyectos mas vendidos
         * @param callback
         */
        function getMasVendidos(callback) {
            get(function (data) {
                var response = data.sort(function (a, b) {
                    return b.vendidos - a.vendidos;
                });

                callback(response.slice(0, 8));
            });
        }

        /**
         * @description Retorna un listado de proyectos filtrando por la categoria
         * @param categoria_id
         * @param callback
         */
        function getByCategoria(categoria_id, callback) {
            var proyectos = [];
            get(function (data) {
                if(data == undefined || data.length == 0)
                    return callback(proyectos);

                data.forEach(function(proyecto){
                    if(proyecto === undefined || proyecto.categorias === undefined || proyecto.categorias.length == 0)
                        return callback(proyectos);

                    if (categoria_id == proyecto.categorias[0].categoria_id)
                        proyectos.push(proyecto);
                });
                return callback(proyectos);
            });
        }

        /** @name: remove
         * @param proyecto_id
         * @param callback
         * @description: Elimina el proyecto seleccionado.
         */
        function remove(proyecto_id, callback) {
            return $http.post(url,
                {function: 'removeProyecto', 'proyecto_id': proyecto_id})
                .success(function (data) {
                    //console.log(data);
                    if (data !== 'false') {
                        ProyectVars.clearCache = true;
                        callback(data);
                    }
                })
                .error(function (data) {
                    callback(data);
                })
        }

        /**
         * @description: Crea un proyecto.
         * @param proyecto
         * @param callback
         * @returns {*}
         */
        function create(proyecto, callback) {

            return $http.post(url,
                {
                    'function': 'createProyecto',
                    'proyecto': JSON.stringify(proyecto)
                })
                .success(function (data) {
                    ProyectVars.clearCache = true;
                    callback(data);
                })
                .error(function (data) {
                    ProyectVars.clearCache = true;
                    callback(data);
                });
        }


        /** @name: update
         * @param proyecto
         * @param callback
         * @description: Realiza update al proyecto.
         */
        function update(proyecto, callback) {
            return $http.post(url,
                {
                    'function': 'updateProyecto',
                    'proyecto': JSON.stringify(proyecto)
                })
                .success(function (data) {
                    ProyectVars.clearCache = true;
                    callback(data);
                })
                .error(function (data) {
                    callback(data);
                });
        }

        /**
         * Para el uso de la p�ginaci�n, definir en el controlador las siguientes variables:
         *
         vm.start = 0;
         vm.pagina = ProyectVars.pagina;
         ProyectVars.paginacion = 5; Cantidad de registros por p�gina
         vm.end = ProyectVars.paginacion;


         En el HTML, en el ng-repeat agregar el siguiente filtro: limitTo:appCtrl.end:appCtrl.start;

         Agregar un bot�n de next:
         <button ng-click="appCtrl.next()">next</button>

         Agregar un bot�n de prev:
         <button ng-click="appCtrl.prev()">prev</button>

         Agregar un input para la p�gina:
         <input type="text" ng-keyup="appCtrl.goToPagina()" ng-model="appCtrl.pagina">

         */


        /**
         * @description: Ir a p�gina
         * @param pagina
         * @returns {*}
         * uso: agregar un m�todo
         vm.goToPagina = function () {
                vm.start= ProyectService.goToPagina(vm.pagina).start;
            };
         */
        function goToPagina(pagina) {

            if (isNaN(pagina) || pagina < 1) {
                ProyectVars.pagina = 1;
                return ProyectVars;
            }

            if (pagina > ProyectVars.paginas) {
                ProyectVars.pagina = ProyectVars.paginas;
                return ProyectVars;
            }

            ProyectVars.pagina = pagina - 1;
            ProyectVars.start = ProyectVars.pagina * ProyectVars.paginacion;
            return ProyectVars;

        }

        /**
         * @name next
         * @description Ir a pr�xima p�gina
         * @returns {*}
         * uso agregar un metodo
         vm.next = function () {
                vm.start = ProyectService.next().start;
                vm.pagina = ProyectVars.pagina;
            };
         */
        function next() {

            if (ProyectVars.pagina + 1 > ProyectVars.paginas) {
                return ProyectVars;
            }
            ProyectVars.start = (ProyectVars.pagina * ProyectVars.paginacion);
            ProyectVars.pagina = ProyectVars.pagina + 1;
            //ProyectVars.end = ProyectVars.start + ProyectVars.paginacion;
            return ProyectVars;
        }

        /**
         * @name previous
         * @description Ir a p�gina anterior
         * @returns {*}
         * uso, agregar un m�todo
         vm.prev = function () {
                vm.start= ProyectService.prev().start;
                vm.pagina = ProyectVars.pagina;
            };
         */
        function prev() {


            if (ProyectVars.pagina - 2 < 0) {
                return ProyectVars;
            }

            //ProyectVars.end = ProyectVars.start;
            ProyectVars.start = (ProyectVars.pagina - 2 ) * ProyectVars.paginacion;
            ProyectVars.pagina = ProyectVars.pagina - 1;
            return ProyectVars;
        }


    }

    ProyectVars.$inject = [];
    /**
     * @description Almacena variables temporales de proyectos
     * @constructor
     */
    function ProyectVars() {
        // Cantidad de p�ginas total del recordset
        this.paginas = 1;
        // P�gina seleccionada
        this.pagina = 1;
        // Cantidad de registros por p�gina
        this.paginacion = 10;
        // Registro inicial, no es p�gina, es el registro
        this.start = 0;


        // Indica si se debe limpiar el cach� la pr�xima vez que se solicite un get
        this.clearCache = true;

    }


    DonationService.$inject = ['$http', 'DonationVars', '$cacheFactory', 'AcUtils'];
    function DonationService($http, DonationVars, $cacheFactory, AcUtils) {
        //Variables
        var service = {};

        var url = currentScriptPath.replace('ac-proyectos.js', '/includes/ac-proyectos.php');

        //Function declarations
        service.get = get;
        service.getByParams = getByParams;

        service.create = create;

        service.update = update;

        service.remove = remove;


        service.goToPagina = goToPagina;
        service.next = next;
        service.prev = prev;

        return service;

        //Functions
        /**
         * @description Obtiene todos los categorias
         * @param callback
         * @returns {*}
         */
        function get(callback) {
            var urlGet = url + '?function=getCategorias';
            var $httpDefaultCache = $cacheFactory.get('$http');
            var cachedData = [];


            // Verifica si existe el cache de categorias
            if ($httpDefaultCache.get(urlGet) != undefined) {
                if (DonationVars.clearCache) {
                    $httpDefaultCache.remove(urlGet);
                }
                else {
                    cachedData = $httpDefaultCache.get(urlGet);
                    callback(cachedData);
                    return;
                }
            }


            return $http.get(urlGet, {cache: true})
                .success(function (data) {
                    $httpDefaultCache.put(urlGet, data);
                    DonationVars.clearCache = false;
                    DonationVars.paginas = (data.length % DonationVars.paginacion == 0) ? parseInt(data.length / DonationVars.paginacion) : parseInt(data.length / DonationVars.paginacion) + 1;
                    callback(data);
                })
                .error(function (data) {
                    callback(data);
                    DonationVars.clearCache = false;
                })
        }


        /**
         * @description Retorna la lista filtrada de categorias
         * @param param -> String, separado por comas (,) que contiene la lista de par�metros de b�squeda, por ej: nombre, sku
         * @param value
         * @param callback
         */
        function getByParams(params, values, exact_match, callback) {
            get(function (data) {

                AcUtils.getByParams(params, values, exact_match, data, callback);
            })
        }

        /** @name: remove
         * @param categoria_id
         * @param callback
         * @description: Elimina el categoria seleccionado.
         */
        function remove(categoria_id, callback) {
            return $http.post(url,
                {function: 'removeCategoria', 'categoria_id': categoria_id})
                .success(function (data) {
                    //console.log(data);
                    if (data !== 'false') {
                        DonationVars.clearCache = true;
                        callback(data);
                    }
                })
                .error(function (data) {
                    callback(data);
                })
        }

        /**
         * @description: Crea un categoria.
         * @param categoria
         * @param callback
         * @returns {*}
         */
        function create(categoria, callback) {

            return $http.post(url,
                {
                    'function': 'createCategoria',
                    'categoria': JSON.stringify(categoria)
                })
                .success(function (data) {
                    DonationVars.clearCache = true;
                    callback(data);
                })
                .error(function (data) {
                    DonationVars.clearCache = true;
                    callback(data);
                });
        }


        /** @name: update
         * @param categoria
         * @param callback
         * @description: Realiza update al categoria.
         */
        function update(categoria, callback) {
            return $http.post(url,
                {
                    'function': 'updateCategoria',
                    'categoria': JSON.stringify(categoria)
                })
                .success(function (data) {
                    DonationVars.clearCache = true;
                    callback(data);
                })
                .error(function (data) {
                    callback(data);
                });
        }

        /**
         * Para el uso de la p�ginaci�n, definir en el controlador las siguientes variables:
         *
         vm.start = 0;
         vm.pagina = DonationVars.pagina;
         DonationVars.paginacion = 5; Cantidad de registros por p�gina
         vm.end = DonationVars.paginacion;


         En el HTML, en el ng-repeat agregar el siguiente filtro: limitTo:appCtrl.end:appCtrl.start;

         Agregar un bot�n de next:
         <button ng-click="appCtrl.next()">next</button>

         Agregar un bot�n de prev:
         <button ng-click="appCtrl.prev()">prev</button>

         Agregar un input para la p�gina:
         <input type="text" ng-keyup="appCtrl.goToPagina()" ng-model="appCtrl.pagina">

         */


        /**
         * @description: Ir a p�gina
         * @param pagina
         * @returns {*}
         * uso: agregar un m�todo
         vm.goToPagina = function () {
                vm.start= DonationService.goToPagina(vm.pagina).start;
            };
         */
        function goToPagina(pagina) {

            if (isNaN(pagina) || pagina < 1) {
                DonationVars.pagina = 1;
                return DonationVars;
            }

            if (pagina > DonationVars.paginas) {
                DonationVars.pagina = DonationVars.paginas;
                return DonationVars;
            }

            DonationVars.pagina = pagina - 1;
            DonationVars.start = DonationVars.pagina * DonationVars.paginacion;
            return DonationVars;

        }

        /**
         * @name next
         * @description Ir a pr�xima p�gina
         * @returns {*}
         * uso agregar un metodo
         vm.next = function () {
                vm.start = DonationService.next().start;
                vm.pagina = DonationVars.pagina;
            };
         */
        function next() {

            if (DonationVars.pagina + 1 > DonationVars.paginas) {
                return DonationVars;
            }
            DonationVars.start = (DonationVars.pagina * DonationVars.paginacion);
            DonationVars.pagina = DonationVars.pagina + 1;
            //DonationVars.end = DonationVars.start + DonationVars.paginacion;
            return DonationVars;
        }

        /**
         * @name previous
         * @description Ir a p�gina anterior
         * @returns {*}
         * uso, agregar un m�todo
         vm.prev = function () {
                vm.start= DonationService.prev().start;
                vm.pagina = DonationVars.pagina;
            };
         */
        function prev() {


            if (DonationVars.pagina - 2 < 0) {
                return DonationVars;
            }

            //DonationVars.end = DonationVars.start;
            DonationVars.start = (DonationVars.pagina - 2 ) * DonationVars.paginacion;
            DonationVars.pagina = DonationVars.pagina - 1;
            return DonationVars;
        }


    }

    DonationVars.$inject = [];
    /**
     * @description Almacena variables temporales de categorias
     * @constructor
     */
    function DonationVars() {
        // Cantidad de p�ginas total del recordset
        this.paginas = 1;
        // P�gina seleccionada
        this.pagina = 1;
        // Cantidad de registros por p�gina
        this.paginacion = 10;
        // Registro inicial, no es p�gina, es el registro
        this.start = 0;


        // Indica si se debe limpiar el cach� la pr�xima vez que se solicite un get
        this.clearCache = true;

    }


    CartService.$inject = ['$http', 'CartVars', '$cacheFactory', 'AcUtils'];
    function CartService($http, CartVars, $cacheFactory, AcUtils) {
        //Variables
        var service = {};


        var url = currentScriptPath.replace('ac-proyectos.js', '/includes/ac-proyectos.php');

        //Function declarations
        service.get = get;
        service.getByParams = getByParams;

        service.create = create; // El carrito se crea si no hay un carrito en estado 0 que se pueda usar. Siempre primero se trae en el controlador, se verifica si existe uno en Iniciado, si no existe se crea.

        service.update = update;

        service.remove = remove;


        service.addToCart = addToCart;
        service.updateProyectInCart = updateProyectInCart;
        service.removeFromCart = removeFromCart;
        service.reloadLastCart = reloadLastCart; // Invoca a getByParam con status 0, si existe cargalo como carrito.
        service.checkOut = checkOut; // Es solo invocar a update con el estado cambiado.

        service.goToPagina = goToPagina;
        service.next = next;
        service.prev = prev;

        return service;

        //Functions
        /**
         * @descripcion Agrega un proyecto al carrito. El proyecto es un extracto del proyecto total (cantidad, precio, proyecto_id, foto[0].url)
         * @param carrito_id
         * @param proyecto
         * @param callback
         */
        function addToCart(carrito_id, proyecto, callback) {
            /*
            var carrito_detalle = {
                carrito_id: carrito_id,
                proyecto_id: proyecto.proyecto_id,
                cantidad: proyecto.cantidad,
                en_oferta: proyecto.en_oferta,
                precio_unitario: proyecto.precio_unitario
            };
            */
            return $http.post(url,
                {
                    'function': 'createCarritoDetalle',
                    'carrito_id': carrito_id,
                    'carrito_detalle': JSON.stringify(proyecto)
                })
                .success(function (data) {


                    // Agrega un detalle al carrito y le avisa a todo el sistema para que se refresque
                    if(data != -1){
                        //carrito_detalle.carrito_detalle_id = data;
                        //CartVars.carrito.push(carrito_detalle);
                        CartVars.carrito.push(data);
                        CartVars.broadcast();
                    }

                    CartVars.clearCache = true;
                    callback(data);
                })
                .error(function (data) {
                    CartVars.clearCache = true;
                    callback(data);
                });
        }

        /**
         * Modifica el detalle de un carrito
         * @param carrito_detalle
         * @param callback
         * @returns {*}
         */
        function updateProyectInCart(carrito_detalle, callback) {
            return $http.post(url,
                {
                    'function': 'updateCarritoDetalle',
                    'carrito_detalle': JSON.stringify(carrito_detalle)
                })
                .success(function (data) {

                    // Agrega un detalle al carrito y le avisa a todo el sistema para que se refresque

                    if(data != -1){
                        var index = 0;
                        for (var i = 0; i<CartVars.carrito.length; i++){
                            if(CartVars.carrito[i].carrito_detalle_id == carrito_detalle.carrito_detalle_id){

                                index = i;
                            }
                        }

                        CartVars.carrito[index] = {};
                        CartVars.carrito[index] = carrito_detalle;
                        CartVars.broadcast();
                    }


                    CartVars.clearCache = true;
                    callback(data);
                })
                .error(function (data) {
                    CartVars.clearCache = true;
                    callback(data);
                });
        }


        /**
         * Remueve un item del carrito
         * @param carrito_detalle_id
         * @param callback
         * @returns {*}
         */
        function removeFromCart(carrito_detalle_id, callback) {
            return $http.post(url,
                {
                    'function': 'removeCarritoDetalle',
                    'carrito_detalle_id': JSON.stringify(carrito_detalle_id)
                })
                .success(function (data) {

                    if(data != -1){
                        var index = 0;
                        for (var i = 0; i<CartVars.carrito.length; i++){
                            if(CartVars.carrito[i].carrito_detalle_id == carrito_detalle.carrito_detalle_id){
                                index = i;
                            }
                        }

                        CartVars.splice(index, 1);
                        CartVars.broadcast();
                    }

                    CartVars.clearCache = true;
                    callback(data);
                })
                .error(function (data) {
                    CartVars.clearCache = true;
                    callback(data);
                });
        }

        /**
         * Retorna el �ltimo carrito en estado Iniciado para el usuario seleccionado.
         * @param usuario_id
         * @param callback
         */
        function reloadLastCart(usuario_id, callback){

            get(usuario_id, function(data){
                AcUtils.getByParams('status', 0, true, data, callback);
            });

        }

        /**
         * Cambia el estado a Pedido
         * @param carrito_id
         * @param callback
         */
        function checkOut(carrito_id, callback){
            update({carrito_id:carrito_id, status:1}, function(data){
                callback(data);
            })
        }


        /**
         * @description Obtiene todos los carritos
         * @param usuario_id, en caso traer todos los carritos, debe ser -1; Est� as� para que si el m�dulo est� en la web, nunca llegue al cliente la lista completa de pedidos;
         * @param callback
         * @returns {*}
         */
        function get(usuario_id, callback) {
            var urlGet = url + '?function=getCarritos&usuario_id='+usuario_id;
            var $httpDefaultCache = $cacheFactory.get('$http');
            var cachedData = [];


            // Verifica si existe el cache de Carritos
            if ($httpDefaultCache.get(urlGet) != undefined) {
                if (CartVars.clearCache) {
                    $httpDefaultCache.remove(urlGet);
                }
                else {
                    cachedData = $httpDefaultCache.get(urlGet);
                    callback(cachedData);
                    return;
                }
            }


            return $http.get(urlGet, {cache: true})
                .success(function (data) {
                    $httpDefaultCache.put(urlGet + usuario_id, data);
                    CartVars.clearCache = false;
                    CartVars.paginas = (data.length % CartVars.paginacion == 0) ? parseInt(data.length / CartVars.paginacion) : parseInt(data.length / CartVars.paginacion) + 1;
                    callback(data);
                })
                .error(function (data) {
                    callback(data);
                    CartVars.clearCache = false;
                })
        }


        /**
         * @description Retorna la lista filtrada de Carritos
         * @param params -> String, separado por comas (,) que contiene la lista de par�metros de b�squeda, por ej: nombre, sku
         * @param values
         * @param exact_match
         * @param usuario_id
         * @param callback
         */
        function getByParams(params, values, exact_match, usuario_id, callback) {
            get(usuario_id, function (data) {
                AcUtils.getByParams(params, values, exact_match, data, callback);
            })
        }


        /** @name: remove
         * @param carrito_id
         * @param callback
         * @description: Elimina el carrito seleccionado.
         */
        function remove(carrito_id, callback) {
            return $http.post(url,
                {function: 'removeCarrito', 'carrito_id': carrito_id})
                .success(function (data) {
                    //console.log(data);
                    if (data !== 'false') {
                        CartVars.clearCache = true;
                        callback(data);
                    }
                })
                .error(function (data) {
                    callback(data);
                })
        }

        /**
         * @description: Crea un carrito.
         * @param carrito
         * @param callback
         * @returns {*}
         */
        function create(carrito, callback) {

            return $http.post(url,
                {
                    'function': 'createCarrito',
                    'carrito': JSON.stringify(carrito)
                })
                .success(function (data) {
                    CartVars.clearCache = true;
                    callback(data);
                })
                .error(function (data) {
                    CartVars.clearCache = true;
                    callback(data);
                });
        }


        /** @name: update
         * @param carrito
         * @param callback
         * @description: Realiza update al carrito.
         */
        function update(carrito, callback) {
            return $http.post(url,
                {
                    'function': 'updateCarrito',
                    'carrito': JSON.stringify(carrito)
                })
                .success(function (data) {
                    CartVars.clearCache = true;
                    callback(data);
                })
                .error(function (data) {
                    callback(data);
                });
        }

        /**
         * Para el uso de la p�ginaci�n, definir en el controlador las siguientes variables:
         *
         vm.start = 0;
         vm.pagina = CartVars.pagina;
         CartVars.paginacion = 5; Cantidad de registros por p�gina
         vm.end = CartVars.paginacion;


         En el HTML, en el ng-repeat agregar el siguiente filtro: limitTo:appCtrl.end:appCtrl.start;

         Agregar un bot�n de next:
         <button ng-click="appCtrl.next()">next</button>

         Agregar un bot�n de prev:
         <button ng-click="appCtrl.prev()">prev</button>

         Agregar un input para la p�gina:
         <input type="text" ng-keyup="appCtrl.goToPagina()" ng-model="appCtrl.pagina">

         */


        /**
         * @description: Ir a p�gina
         * @param pagina
         * @returns {*}
         * uso: agregar un m�todo
         vm.goToPagina = function () {
                vm.start= CartService.goToPagina(vm.pagina).start;
            };
         */
        function goToPagina(pagina) {

            if (isNaN(pagina) || pagina < 1) {
                CartVars.pagina = 1;
                return CartVars;
            }

            if (pagina > CartVars.paginas) {
                CartVars.pagina = CartVars.paginas;
                return CartVars;
            }

            CartVars.pagina = pagina - 1;
            CartVars.start = CartVars.pagina * CartVars.paginacion;
            return CartVars;

        }

        /**
         * @name next
         * @description Ir a pr�xima p�gina
         * @returns {*}
         * uso agregar un metodo
         vm.next = function () {
                vm.start = CartService.next().start;
                vm.pagina = CartVars.pagina;
            };
         */
        function next() {

            if (CartVars.pagina + 1 > CartVars.paginas) {
                return CartVars;
            }
            CartVars.start = (CartVars.pagina * CartVars.paginacion);
            CartVars.pagina = CartVars.pagina + 1;
            //CartVars.end = CartVars.start + CartVars.paginacion;
            return CartVars;
        }

        /**
         * @name previous
         * @description Ir a p�gina anterior
         * @returns {*}
         * uso, agregar un m�todo
         vm.prev = function () {
                vm.start= CartService.prev().start;
                vm.pagina = CartVars.pagina;
            };
         */
        function prev() {


            if (CartVars.pagina - 2 < 0) {
                return CartVars;
            }

            //CartVars.end = CartVars.start;
            CartVars.start = (CartVars.pagina - 2 ) * CartVars.paginacion;
            CartVars.pagina = CartVars.pagina - 1;
            return CartVars;
        }


    }

    CartVars.$inject = ['$rootScope'];
    /**
     * @description Almacena variables temporales de Carritos
     * @param $rootScope
     * @constructor
     */
    function CartVars($rootScope) {
        // Cantidad de p�ginas total del recordset
        this.paginas = 1;
        // P�gina seleccionada
        this.pagina = 1;
        // Cantidad de registros por p�gina
        this.paginacion = 10;
        // Registro inicial, no es p�gina, es el registro
        this.start = 0;

        // Carrito Temporal
        this.carrito = [];
        // Total de proyectos
        this.carrito_cantidad_proyectos = function(){
            var cantidad = 0;
            for(var i=0; i<this.carrito.length;i++){
                cantidad = cantidad + this.carrito[i].cantidad;
            }
            return cantidad;
        };
        // Total precio
        this.carrito_total = function(){
            var precio = 0.0;
            for(var i=0; i<this.carrito.length;i++){
                precio = precio + (this.carrito[i].cantidad * this.carrito[i].precio_unitario);
            }
            return precio;
        };


        // Indica si se debe limpiar el cach� la pr�xima vez que se solicite un get
        this.clearCache = true;

        // Transmite el aviso de actualizaci�n
        this.broadcast = function () {
            $rootScope.$broadcast("CartVars")
        };

        // Recibe aviso de actualizaci�n
        this.listen = function (callback) {
            $rootScope.$on("CartVars", callback)
        };

    }

})();