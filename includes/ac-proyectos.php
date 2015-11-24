<?php
/* TODO:
 * */


session_start();

require 'PHPMailerAutoload.php';

// Token
$decoded_token = null;

if (file_exists('../../../includes/MyDBi.php')) {
    require_once '../../../includes/MyDBi.php';
    require_once '../../../includes/config.php';
} else {
    require_once 'MyDBi.php';
}

$data = file_get_contents("php://input");

// Decode data from js
$decoded = json_decode($data);


// Si la seguridad está activa
if ($jwt_enabled) {

    // Carga el jwt_helper
    if (file_exists('../../../jwt_helper.php')) {
        require_once '../../../jwt_helper.php';
    } else {
        require_once 'jwt_helper.php';
    }


    // Las funciones en el if no necesitan usuario logged
    if (($decoded == null) && (($_GET["function"] != null) &&
            ($_GET["function"] == 'getProyectos' ||
                $_GET["function"] == 'getComentarios' ||
                $_GET["function"] == 'getDonaciones'))
    ) {
        $token = '';
    } else {
        checkSecurity();
    }

}


if ($decoded != null) {
    if ($decoded->function == 'createProyecto') {
        createProyecto($decoded->proyecto);
    } else if ($decoded->function == 'createProyectoCambio') {
        createProyectoCambio($decoded->proyecto_cambio);
    } else if ($decoded->function == 'createComentario') {
        createComentario($decoded->comentario);
    } else if ($decoded->function == 'createDonacion') {
        createDonacion($decoded->donacion);
    } else if ($decoded->function == 'updateProyecto') {
        updateProyecto($decoded->proyecto);
    } else if ($decoded->function == 'updateProyectoCambio') {
        updateProyectoCambio($decoded->proyecto_cambio);
    } else if ($decoded->function == 'updateComentario') {
        updateComentario($decoded->comentario);
    } else if ($decoded->function == 'updateDonacion') {
        updateDonacion($decoded->donacion);
    } else if ($decoded->function == 'removeProyecto') {
        removeProyecto($decoded->proyecto_id);
    } else if ($decoded->function == 'removeComentario') {
        removeComentario($decoded->comentario_id);
    } else if ($decoded->function == 'removeDonacion') {
        removeDonacion($decoded->donacion_id);
    }
} else {
    $function = $_GET["function"];
    if ($function == 'getProyectos') {
        getProyectos();
    } elseif ($function == 'getComentarios') {
        getComentarios($_GET["proyecto_id"]);
    } elseif ($function == 'getDonaciones') {
        getDonaciones($_GET["usuario_id"]);
    } elseif ($function == 'getProyectoCambio') {
        getProyectoCambio($_GET["proyecto_id"]);
    }
}

/////// INSERT ////////
/**
 * @description Crea un proyecto y sus fotos
 * @param $proyect
 */
function createProyecto($proyect)
{
    $db = new MysqliDb();
    $db->startTransaction();
    $proyect_decoded = checkProyecto(json_decode($proyect));

    $data = array(
        'nombre' => $proyect_decoded->nombre,
        'descripcion' => $proyect_decoded->descripcion,
        'costo_inicial' => $proyect_decoded->costo_inicial,
        'total_donado' => $proyect_decoded->total_donado,
        'fecha_inicio' => $proyect_decoded->fecha_inicio,
        'fecha_fin' => $proyect_decoded->fecha_fin,
        'status' => $proyect_decoded->status,
        'usuario_id' => $proyect_decoded->usuario_id
    );

    $result = $db->insert('proyectos', $data);
    if ($result > -1) {

        foreach ($proyect_decoded->fotos as $foto) {
            if (!createFotos($foto, $result, $db)) {
                $db->rollback();
                echo json_encode(-1);
                return;
            }
        }

        $db->commit();
        echo json_encode($result);
    } else {
        $db->rollback();
        echo json_encode(-1);
    }
}

/**
 * @description Crea un proyecto y sus fotos
 * @param $proyect
 */
function createProyectoCambio($proyecto_cambio)
{
    $db = new MysqliDb();
    $db->startTransaction();
    $proyect_decoded = checkProyecto(json_decode($proyecto_cambio));

    $data = array(
        'proyecto_id' => $proyect_decoded->proyecto_id,
        'nombre' => $proyect_decoded->nombre,
        'descripcion' => $proyect_decoded->descripcion,
        'costo_inicial' => $proyect_decoded->pto_repo,
        'fecha_fin' => $proyect_decoded->status,
        'status' => $proyect_decoded->vendidos,
        'justificacion' => $proyect_decoded->justificacion,
        'respuesta' => $proyect_decoded->respuesta,
        'fotos' => $proyect_decoded->fotos
    );

    $result = $db->insert('proyectos', $data);
    if ($result > -1) {

        foreach ($proyect_decoded->fotos as $foto) {
            if (!createFotos($foto, $result, $db)) {
                $db->rollback();
                echo json_encode(-1);
                return;
            }
        }

        $db->commit();
        echo json_encode($result);
    } else {
        $db->rollback();
        echo json_encode(-1);
    }
}

/**
 * @description Crea la relación entre un proyecto y una categoría
 * @param $comentario
 * @param $proyecto_id
 * @param $db
 * @return bool
 */
function createComentarios($comentario, $proyecto_id, $db)
{
    $data = array(
        'proyecto_id' => $proyecto_id,
        'titulo' => $comentario->titulo,
        'detalles' => $comentario->detalles,
        'parent_id' => $comentario->parent_id,
        'creador_id' => $comentario->creador_id,
        'votos_up' => $comentario->votos_up,
        'votos_down' => $comentario->votos_down,
        'fecha' => $comentario->fecha
    );

    $cat = $db->insert('proyectos_comentarios', $data);
    return ($cat > -1) ? true : false;
}

/**
 * @description Crea una foto para un proyecto determinado, main == 1 significa que la foto es la principal
 * @param $foto
 * @param $proyecto_id
 * @param $db
 * @return bool
 */
function createFotos($foto, $proyecto_id, $db)
{
    $data = array(
        'main' => $foto->main,
        'nombre' => $foto->nombre,
        'carpeta' => $foto->carpeta,
        'proyecto_id' => $proyecto_id
    );

    $fot = $db->insert('proyectos_fotos', $data);
    return ($fot > -1) ? true : false;
}

/**
 * @description Crea una categoría, esta es la tabla paramétrica, la funcion createComentarioS crea las relaciones
 * @param $comentario
 */
function createComentario($comentario)
{
    $db = new MysqliDb();
    $db->startTransaction();
    $comentario_decoded = checkComentario(json_decode($comentario));

    $data = array(
        'proyecto_id' => $comentario_decoded->proyecto_id,
        'titulo' => $comentario_decoded->titulo,
        'detalles' => $comentario_decoded->detalles,
        'parent_id' => $comentario_decoded->parent_id,
        'creador_id' => $comentario_decoded->creador_id,
        'votos_up' => $comentario_decoded->votos_up,
        'votos_down' => $comentario_decoded->votos_down,
        'fecha' => $comentario_decoded->fecha
    );

    $result = $db->insert('proyectos_comentarios', $data);
    if ($result > -1) {
        $db->commit();
        echo json_encode($result);
    } else {
        $db->rollback();
        echo json_encode(-1);
    }
}

/**
 * @description Crea un donacion y su detalle
 * @param $donacion
 */
function createDonacion($donacion)
{
    $db = new MysqliDb();
    $db->startTransaction();
    $donacion_decoded = checkDonacion(json_decode($donacion));

    $data = array(
        'proyecto_id' => $donacion_decoded->proyecto_id,
        'donador_id' => $donacion_decoded->donador_id,
        'fecha' => $donacion_decoded->fecha,
        'costo_inicial' => $donacion_decoded->costo_inicial,
        'status' => $donacion_decoded->status,
        'comprobante' => $donacion_decoded->comprobante
    );

    $result = $db->insert('donaciones', $data);
    if ($result > -1) {

        $db->commit();
        echo json_encode($result);
    } else {
        $db->rollback();
        echo json_encode(-1);
    }
}

/////// UPDATE ////////

/**
 * @description Modifica un proyecto, sus fotos, precios y le asigna las comentarios
 * @param $proyect
 */
function updateProyecto($proyect)
{
    $db = new MysqliDb();
    $db->startTransaction();
    $proyect_decoded = checkProyecto(json_decode($proyect));

    $db->where('proyecto_id', $proyect_decoded->proyecto_id);
    $data = array(
        'nombre' => $proyect_decoded->nombre,
        'descripcion' => $proyect_decoded->descripcion,
        'costo_inicial' => $proyect_decoded->costo_inicial,
        'fecha_inicio' => $proyect_decoded->fecha_inicio,
        'fecha_fin' => $proyect_decoded->fecha_fin,
        'status' => $proyect_decoded->status,
        'usuario_id' => $proyect_decoded->usuario_id
    );

    $result = $db->update('proyectos', $data);

    $db->where('proyecto_id', $proyect_decoded->proyecto_id);
    $db->delete('proyectos_fotos');

    if ($result) {

        foreach ($proyect_decoded->fotos as $foto) {
            if (!createFotos($foto, $proyect_decoded->proyecto_id, $db)) {
                $db->rollback();
                echo json_encode(-1);
                return;
            }
        }
        

        $db->commit();
        echo json_encode($result);
    } else {
        $db->rollback();
        echo json_encode(-1);
    }
}

/**
 * @param $proyecto_cambio
 */
function updateProyectoCambio($proyecto_cambio){
    $db = new MysqliDb();
    $db->startTransaction();
    $cambio_decoded = checkProyectoCambio(json_decode($proyecto_cambio));
    $db->where('proyecto_cambio_id', $cambio_decoded->proyecto_cambio_id);
    $data = array(
        'respuesta' => $cambio_decoded->respuesta,
        'status' => $cambio_decoded->status
    );

    $result = $db->update('proyectos_cambios', $data);
    if ($result) {
        $db->commit();
        echo json_encode($result);
    } else {
        $db->rollback();
        echo json_encode(-1);
    }
}

/**
 * @description Modifica una comentario
 * @param $comentario
 */
function updateComentario($comentario)
{
    $db = new MysqliDb();
    $db->startTransaction();
    $comentario_decoded = checkComentario(json_decode($comentario));
    $db->where('comentario_id', $comentario_decoded->comentario_id);
    $data = array(
        'proyecto_id' => $comentario_decoded->proyecto_id,
        'titulo' => $comentario_decoded->titulo,
        'detalles' => $comentario_decoded->detalles,
        'parent_id' => $comentario_decoded->parent_id,
        'creador_id' => $comentario_decoded->creador_id,
        'votos_up' => $comentario_decoded->votos_up,
        'votos_down' => $comentario_decoded->votos_down,
        'fecha' => $comentario_decoded->fecha
    );

    $result = $db->update('proyectos_comentarios', $data);
    if ($result) {
        $db->commit();
        echo json_encode($result);
    } else {
        $db->rollback();
        echo json_encode(-1);
    }
}

/**
 * @description Modifica un donacion
 * @param $donacion
 */
function updateDonacion($donacion)
{
    $db = new MysqliDb();
    $db->startTransaction();
    $donacion_decoded = checkDonacion(json_decode($donacion));
    $db->where('donacion_id', $donacion_decoded->donacion_id);
    $data = array(
        'proyecto_id' => $donacion_decoded->proyecto_id,
        'donador_id' => $donacion_decoded->donador_id,
        'fecha' => $donacion_decoded->fecha,
        'valor' => $donacion_decoded->valor,
        'status' => $donacion_decoded->status,
        'comprobante' => $donacion_decoded->comprobante
    );

    $result = $db->update('donaciones', $data);
    if ($result) {

        $db->commit();
        echo json_encode($result);
    } else {
        $db->rollback();
        echo json_encode(-1);
    }
}

/////// REMOVE ////////

/**
 * @description Elimina un proyecto, sus precios, sus fotos, sus comentarios y sus kits
 * @param $proyecto_id
 */
function removeProyecto($proyecto_id)
{
    $db = new MysqliDb();

    $db->where("proyecto_id", $proyecto_id);
    $results = $db->delete('proyectos');

    $db->where("proyecto_id", $proyecto_id);
    $db->delete('fotos');


    if ($results) {

        echo json_encode(1);
    } else {
        echo json_encode(-1);

    }
}

/**
 * @description Elimina una comentario
 * @param $comentario_id
 */
function removeComentario($comentario_id)
{
    $db = new MysqliDb();

    $db->where("comentario_id", $comentario_id);
    $results = $db->delete('proyectos_comentarios');

    if ($results) {

        echo json_encode(1);
    } else {
        echo json_encode(-1);

    }
}


/**
 * @description Elimina un donacion. Esta funcionalidad no tiene una función específica ya que un donacion se da de baja lógica unicamente, no física.
 * @param $donacion_id
 */
function removeDonacion($donacion_id)
{
    $db = new MysqliDb();

    $db->where("donacion_id", $donacion_id);
    $results = $db->delete('donaciones');

    if ($results) {

        echo json_encode(1);
    } else {
        echo json_encode(-1);

    }
}

/////// GET ////////

/**
 * @descr Obtiene los proyectos
 */
function getProyectos()
{
    $db = new MysqliDb();

//    $results = $db->get('proyectos');
    $results = $db->rawQuery('SELECT
    p.proyecto_id,
    p.nombre nombreProyecto,
    p.descripcion,
    p.costo_inicial,
    p.total_donado,
    p.fecha_inicio,
    p.fecha_fin,
    p.status,
    c.proyecto_comentario_id,
    c.titulo,
    c.detalles,
    c.creador_id,
    c.parent_id,
    c.votos_up,
    c.votos_down,
    c.fecha,
    f.proyecto_foto_id,
    f.main,
    f.nombre nombreFoto,
    f.carpeta,
    d.donacion_id,
    d.donador_id,
    d.fecha,
    d.valor,
    d.status,
    d.comprobante,
    u.usuario_id,
    u.nombre nombreUsuario,
    u.apellido
FROM
    proyectos p
        LEFT JOIN
    proyectos_comentarios pc ON p.proyecto_id = pc.proyecto_id
        LEFT JOIN
    comentarios c ON c.comentario_id = pc.comentario_id
        LEFT JOIN
    precios pr ON p.proyecto_id = pr.proyecto_id
        LEFT JOIN
    proyectos_fotos f ON p.proyecto_id = f.proyecto_id
        LEFT JOIN
    proyectos_kits ps ON p.proyecto_id = ps.parent_id
        LEFT JOIN
    proyectos_proveedores pro ON pro.proyecto_id = p.proyecto_id
        LEFT JOIN
    usuarios u ON u.usuario_id = pro.proveedor_id
GROUP BY p.proyecto_id , p.nombre , p.descripcion , p.pto_repo , p.sku , p.status , 
p.vendidos , p.destacado , p.proyecto_tipo , p.en_slider , p.en_oferta , c.comentario_id , 
c.nombre , c.parent_id , ps.proyecto_kit_id , ps.proyecto_id , ps.proyecto_cantidad , pr.precio_id , pr.precio_tipo_id , 
pr.precio, f.proyecto_foto_id, f.main, f.nombre, u.usuario_id, u.nombre, u.apellido
;');


    $final = array();
    foreach ($results as $row) {

        if (!isset($final[$row["proyecto_id"]])) {
            $final[$row["proyecto_id"]] = array(
                'proyecto_id' => $row["proyecto_id"],
                'nombre' => $row["nombreProyecto"],
                'descripcion' => $row["descripcion"],
                'pto_repo' => $row["pto_repo"],
                'sku' => $row["sku"],
                'status' => $row["status"],
                'vendidos' => $row["vendidos"],
                'destacado' => $row["destacado"],
                'proyecto_tipo' => $row["proyecto_tipo"],
                'en_slider' => $row["en_slider"],
                'en_oferta' => $row["en_oferta"],
                'comentarios' => array(),
                'precios' => array(),
                'fotos' => array(),
                'kits' => array(),
                'proveedores' => array()
            );
        }
        $have_cat = false;
        if ($row["comentario_id"] !== null) {

            if (sizeof($final[$row['proyecto_id']]['comentarios']) > 0) {
                foreach ($final[$row['proyecto_id']]['comentarios'] as $cat) {
                    if ($cat['comentario_id'] == $row["comentario_id"]) {
                        $have_cat = true;
                    }
                }
            } else {
                $final[$row['proyecto_id']]['comentarios'][] = array(
                    'comentario_id' => $row['comentario_id'],
                    'nombre' => $row['nombreComentario'],
                    'parent_id' => $row['parent_id']
                );

                $have_cat = true;
            }

            if (!$have_cat) {
                array_push($final[$row['proyecto_id']]['comentarios'], array(
                    'comentario_id' => $row['comentario_id'],
                    'nombre' => $row['nombreComentario'],
                    'parent_id' => $row['parent_id']
                ));
            }
        }


        $have_pre = false;
        if ($row["precio_id"] !== null) {

            if (sizeof($final[$row['proyecto_id']]['precios']) > 0) {
                foreach ($final[$row['proyecto_id']]['precios'] as $cat) {
                    if ($cat['precio_id'] == $row["precio_id"]) {
                        $have_pre = true;
                    }
                }
            } else {
                $final[$row['proyecto_id']]['precios'][] = array(
                    'precio_id' => $row['precio_id'],
                    'precio_tipo_id' => $row['precio_tipo_id'],
                    'precio' => $row['precio']
                );

                $have_pre = true;
            }

            if (!$have_pre) {
                array_push($final[$row['proyecto_id']]['precios'], array(
                    'precio_id' => $row['precio_id'],
                    'precio_tipo_id' => $row['precio_tipo_id'],
                    'precio' => $row['precio']
                ));
            }
        }


        $have_fot = false;
        if ($row["proyecto_foto_id"] !== null) {

            if (sizeof($final[$row['proyecto_id']]['fotos']) > 0) {
                foreach ($final[$row['proyecto_id']]['fotos'] as $cat) {
                    if ($cat['proyecto_foto_id'] == $row["proyecto_foto_id"]) {
                        $have_fot = true;
                    }
                }
            } else {
                $final[$row['proyecto_id']]['fotos'][] = array(
                    'proyecto_foto_id' => $row['proyecto_foto_id'],
                    'nombre' => $row['nombreFoto'],
                    'main' => $row['main']
                );

                $have_fot = true;
            }

            if (!$have_fot) {
                array_push($final[$row['proyecto_id']]['fotos'], array(
                    'proyecto_foto_id' => $row['proyecto_foto_id'],
                    'nombre' => $row['nombreFoto'],
                    'main' => $row['main']
                ));
            }
        }

        $have_kit = false;
        if ($row["proyecto_kit_id"] !== null) {

            if (sizeof($final[$row['proyecto_id']]['kits']) > 0) {
                foreach ($final[$row['proyecto_id']]['kits'] as $cat) {
                    if ($cat['proyecto_kit_id'] == $row["proyecto_kit_id"]) {
                        $have_kit = true;
                    }
                }
            } else {
                $final[$row['proyecto_id']]['kits'][] = array(
                    'proyecto_kit_id' => $row['proyecto_kit_id'],
                    'proyecto_id' => $row['proyectoKit'],
                    'proyecto_cantidad' => $row['proyecto_cantidad']
                );

                $have_kit = true;
            }

            if (!$have_kit) {
                array_push($final[$row['proyecto_id']]['kits'], array(
                    'proyecto_kit_id' => $row['proyecto_kit_id'],
                    'proyecto_id' => $row['proyectoKit'],
                    'proyecto_cantidad' => $row['proyecto_cantidad']
                ));
            }
        }


        $have_pro = false;
        if ($row["usuario_id"] !== null) {

            if (sizeof($final[$row['proyecto_id']]['proveedores']) > 0) {
                foreach ($final[$row['proyecto_id']]['proveedores'] as $cat) {
                    if ($cat['usuario_id'] == $row["usuario_id"]) {
                        $have_pro = true;
                    }
                }
            } else {
                $final[$row['proyecto_id']]['proveedores'][] = array(
                    'usuario_id' => $row['usuario_id'],
                    'nombre' => $row['nombreUsuario'],
                    'apellido' => $row['apellido']
                );

                $have_pro = true;
            }

            if (!$have_pro) {
                array_push($final[$row['proyecto_id']]['proveedores'], array(
                    'usuario_id' => $row['usuario_id'],
                    'nombre' => $row['nombreUsuario'],
                    'apellido' => $row['apellido']
                ));
            }
        }
    }
    echo json_encode(array_values($final));
}


/**
 * @descr Obtiene las comentarios
 */
function getComentarios()
{
    $db = new MysqliDb();
    $results = $db->rawQuery('SELECT c.*, (select count(proyecto_id) from proyectos_comentarios p where p.comentario_id= c.comentario_id) total, d.nombre nombrePadre FROM comentarios c LEFT JOIN comentarios d ON c.parent_id = d.comentario_id;');


    echo json_encode($results);
}

/**
 * @description Retorna las donaciones, en caso de ser la consulta de un usuario, solo trae las del usuario
 * @param $usuario_id
 */
function getDonaciones($usuario_id)
{
    $db = new MysqliDb();

    $where = '';
    if ($usuario_id != -1) {
        $where = 'c.donador_id in (select usuario_id from proyectos p1 where p1.proyecto_id = p.proyecto_id)';
    }
    $results = $db->rawQuery('donaciones c', null, 'c.donacion_id, c.status, c.total, c.fecha, c.usuario_id, u.nombre, u.apellido');

    foreach ($results as $key => $row) {
        $db = new MysqliDb();
        $db->where('donacion_id', $row['donacion_id']);
        $db->join("proyectos p", "p.proyecto_id=c.proyecto_id", "LEFT");
        $proyectos = $db->get('donacion_detalles c', null, 'c.donacion_detalle_id, c.donacion_id, c.proyecto_id, p.nombre, c.cantidad, c.en_oferta, c.precio_unitario');
        $results[$key]['proyectos'] = $proyectos;
    }
    echo json_encode($results);
}

/**
 * @description Verifica todos los campos de proyecto para que existan
 * @param $proyecto
 * @return mixed
 */
function checkProyecto($proyecto)
{


    $proyecto->nombre = (!array_key_exists("nombre", $proyecto)) ? '' : $proyecto->nombre;
    $proyecto->descripcion = (!array_key_exists("descripcion", $proyecto)) ? '' : $proyecto->descripcion;
    $proyecto->costo_inicial = (!array_key_exists("costo_inicial", $proyecto)) ? 0 : $proyecto->costo_inicial;
    $proyecto->total_donado = (!array_key_exists("total_donado", $proyecto)) ? '' : $proyecto->total_donado;
    $proyecto->fecha_inicio = (!array_key_exists("fecha_inicio", $proyecto)) ? 1 : $proyecto->fecha_inicio;
    $proyecto->fecha_fin = (!array_key_exists("fecha_fin", $proyecto)) ? 0 : $proyecto->fecha_fin;
    $proyecto->status = (!array_key_exists("status", $proyecto)) ? 0 : $proyecto->status;
    $proyecto->usuario_id = (!array_key_exists("usuario_id", $proyecto)) ? 0 : $proyecto->usuario_id;
    $proyecto->fotos = (!array_key_exists("fotos", $proyecto)) ? array() : checkFotos($proyecto->fotos);

    return $proyecto;
}

/**
 * @description Verifica todos los campos de fotos para que existan
 * @param $fotos
 * @return mixed
 */
function checkFotos($fotos)
{
    foreach ($fotos as $foto) {
        $foto->proyecto_id = (!array_key_exists("proyecto_id", $foto)) ? 0 : $foto->proyecto_id;
        $foto->nombre = (!array_key_exists("nombre", $foto)) ? '' : $foto->nombre;
        $foto->main = (!array_key_exists("main", $foto)) ? 0 : $foto->main;
        $foto->carpeta = (!array_key_exists("carpeta", $foto)) ? 0 : $foto->carpeta;
    }
    return $fotos;
}

/**
 * @description Verifica todos los campos de comentario del proyecto para que existan
 * @param $comentarios
 * @return mixed
 */
function checkComentarios($comentarios)
{
    foreach ($comentarios as $comentario) {
        $comentario->proyecto_id = (!array_key_exists("proyecto_id", $comentario)) ? 0 : $comentario->proyecto_id;
        $comentario->titulo = (!array_key_exists("titulo", $comentario)) ? 0 : $comentario->titulo;
        $comentario->detalles = (!array_key_exists("detalles", $comentario)) ? 0 : $comentario->detalles;
        $comentario->parent_id = (!array_key_exists("parent_id", $comentario)) ? 0 : $comentario->parent_id;
        $comentario->creador_id = (!array_key_exists("creador_id", $comentario)) ? 0 : $comentario->creador_id;
        $comentario->votos_up = (!array_key_exists("votos_up", $comentario)) ? 0 : $comentario->votos_up;
        $comentario->votos_down = (!array_key_exists("votos_down", $comentario)) ? 0 : $comentario->votos_down;
        $comentario->fecha = (!array_key_exists("fecha", $comentario)) ? 0 : $comentario->fecha;
    }

    return $comentarios;
}

/**
 * @description Verifica todos los campos de comentario para que existan
 * @param $comentario
 * @return mixed
 */
function checkComentario($comentario)
{
    $comentario->proyecto_id = (!array_key_exists("proyecto_id", $comentario)) ? 0 : $comentario->proyecto_id;
    $comentario->titulo = (!array_key_exists("titulo", $comentario)) ? 0 : $comentario->titulo;
    $comentario->detalles = (!array_key_exists("detalles", $comentario)) ? 0 : $comentario->detalles;
    $comentario->parent_id = (!array_key_exists("parent_id", $comentario)) ? 0 : $comentario->parent_id;
    $comentario->creador_id = (!array_key_exists("creador_id", $comentario)) ? 0 : $comentario->creador_id;
    $comentario->votos_up = (!array_key_exists("votos_up", $comentario)) ? 0 : $comentario->votos_up;
    $comentario->votos_down = (!array_key_exists("votos_down", $comentario)) ? 0 : $comentario->votos_down;
    $comentario->fecha = (!array_key_exists("fecha", $comentario)) ? 0 : $comentario->fecha;

    return $comentario;
}

/**
 * @description Verifica todos los campos de donacion para que existan
 * @param $donacion
 * @return mixed
 */
function checkDonacion($donacion)
{
    $now = new DateTime(null, new DateTimeZone('America/Argentina/Buenos_Aires'));

    $donacion->proyecto_id = (!array_key_exists("proyecto_id", $donacion)) ? 1 : $donacion->proyecto_id;
    $donacion->donador_id = (!array_key_exists("donador_id", $donacion)) ? 0.0 : $donacion->donador_id;
    $donacion->fecha = (!array_key_exists("fecha", $donacion)) ? $now->format('Y-m-d H:i:s') : $donacion->fecha;
    $donacion->costo_inicial = (!array_key_exists("costo_inicial", $donacion)) ? -1 : $donacion->costo_inicial;
    $donacion->status = (!array_key_exists("status", $donacion)) ? -1 : $donacion->status;
    $donacion->comprobante = (!array_key_exists("comprobante", $donacion)) ? -1 : $donacion->comprobante;

    return $donacion;
}

