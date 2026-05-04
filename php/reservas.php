<?php
session_start();

require_once 'Bd.php';
require_once 'Usuario.php';
require_once 'Recurso.php';
require_once 'Presupuesto.php';
require_once 'Reserva.php';

class Controlador {

    private string  $accion;
    private ?int    $usuarioId;
    private ?string $usuarioNombre;
    private string  $mensajeExito = '';
    private string  $mensajeError = '';

    private array   $recursos     = [];
    private ?object $recurso      = null;
    private ?object $presupuesto  = null;
    private array   $reservas     = [];
    private string  $breadcrumb   = '';
    private string  $tituloPagina = 'Reservas | Turismo Valencia';

    public function __construct() {
        $this->accion        = $_GET['accion'] ?? 'inicio';
        $this->usuarioId     = $_SESSION['usuario_id']     ?? null;
        $this->usuarioNombre = $_SESSION['usuario_nombre'] ?? null;
    }

    public function ejecutar(): void {
        switch ($this->accion) {
            case 'registro':            $this->accionRegistro();           break;
            case 'login':               $this->accionLogin();              break;
            case 'logout':              $this->accionLogout();             break;
            case 'detalle':             $this->accionDetalle();            break;
            case 'generar_presupuesto': $this->accionGenerarPresupuesto(); break;
            case 'confirmar_reserva':   $this->accionConfirmarReserva();   break;
            case 'mis_reservas':        $this->accionMisReservas();        break;
            case 'anular':              $this->accionAnular();             break;
            default:                    $this->accionInicio();
        }
        $this->renderizarPagina();
    }

    private function accionInicio(): void {
        $this->breadcrumb = '<a href="index.html">INICIO</a> >> <strong>Reservas</strong>';
        $this->recursos   = Recurso::obtenerTodos();
    }

    private function accionRegistro(): void {
        $this->breadcrumb   = '<a href="index.html">INICIO</a> >> <a href="reservas.php">Reservas</a> >> <strong>Registro</strong>';
        $this->tituloPagina = 'Registro | Turismo Valencia';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $nombre    = trim($_POST['nombre']    ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email     = trim($_POST['email']     ?? '');
        $password  = $_POST['password']       ?? '';
        $password2 = $_POST['password2']      ?? '';

        if (empty($nombre) || empty($apellidos) || empty($email) || empty($password)) {
            $this->mensajeError = 'Todos los campos son obligatorios.';
            return;
        }

        if (strlen($password) < 6) {
            $this->mensajeError = 'La contrasena debe tener al menos 6 caracteres.';
            return;
        }

        if ($password !== $password2) {
            $this->mensajeError = 'Las contrasenas no coinciden.';
            return;
        }

        $resultado = Usuario::registrar($nombre, $apellidos, $email, $password);

        if ($resultado === true) {
            $this->mensajeExito = 'Registro completado. Ya puedes iniciar sesion.';
            $this->accion       = 'login';
            $this->breadcrumb   = '<a href="index.html">INICIO</a> >> <a href="reservas.php">Reservas</a> >> <strong>Acceder</strong>';
        } else {
            $this->mensajeError = $resultado;
        }
    }

    private function accionLogin(): void {
        $this->breadcrumb   = '<a href="index.html">INICIO</a> >> <a href="reservas.php">Reservas</a> >> <strong>Acceder</strong>';
        $this->tituloPagina = 'Acceder | Turismo Valencia';

        if ($this->usuarioId !== null) {
            header('Location: reservas.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';

        if (empty($email) || empty($password)) {
            $this->mensajeError = 'Correo y contrasena son obligatorios.';
            return;
        }

        $usuario = Usuario::login($email, $password);

        if ($usuario === null) {
            $this->mensajeError = 'Correo electronico o contrasena incorrectos.';
            return;
        }

        $_SESSION['usuario_id']     = $usuario->getId();
        $_SESSION['usuario_nombre'] = $usuario->getNombreCompleto();
        $this->usuarioId            = $usuario->getId();
        $this->usuarioNombre        = $usuario->getNombreCompleto();

        header('Location: reservas.php');
        exit;
    }

    private function accionLogout(): void {
        session_unset();
        session_destroy();
        header('Location: reservas.php');
        exit;
    }

    private function accionDetalle(): void {
        $id            = (int)($_GET['id'] ?? 0);
        $this->recurso = Recurso::obtenerPorId($id);
        $this->breadcrumb   = '<a href="index.html">INICIO</a> >> <a href="reservas.php">Reservas</a> >> <strong>Detalle del recurso</strong>';
        $this->tituloPagina = 'Detalle | Turismo Valencia';

        if ($this->recurso === null) {
            $this->mensajeError = 'El recurso solicitado no existe.';
            $this->accion       = 'inicio';
            $this->recursos     = Recurso::obtenerTodos();
            $this->breadcrumb   = '<a href="index.html">INICIO</a> >> <strong>Reservas</strong>';
        }
    }

    private function accionGenerarPresupuesto(): void {
        $this->breadcrumb   = '<a href="index.html">INICIO</a> >> <a href="reservas.php">Reservas</a> >> <strong>Presupuesto</strong>';
        $this->tituloPagina = 'Presupuesto | Turismo Valencia';

        if ($this->usuarioId === null) {
            $this->mensajeError = 'Debes iniciar sesion para generar un presupuesto.';
            $this->accion       = 'login';
            $this->breadcrumb   = '<a href="index.html">INICIO</a> >> <a href="reservas.php">Reservas</a> >> <strong>Acceder</strong>';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: reservas.php');
            exit;
        }

        $recursoId   = (int)($_POST['recurso_id']  ?? 0);
        $numPersonas = (int)($_POST['num_personas'] ?? 0);

        $recurso = Recurso::obtenerPorId($recursoId);

        if ($recurso === null || $recursoId <= 0 || $numPersonas <= 0) {
            $this->mensajeError = 'Datos del formulario no validos.';
            $this->accion       = 'inicio';
            $this->recursos     = Recurso::obtenerTodos();
            $this->breadcrumb   = '<a href="index.html">INICIO</a> >> <strong>Reservas</strong>';
            return;
        }

        if ($numPersonas > $recurso->getPlazasDisponibles()) {
            $this->mensajeError = 'No hay suficientes plazas disponibles.';
            $this->accion       = 'detalle';
            $this->recurso      = $recurso;
            $this->breadcrumb   = '<a href="index.html">INICIO</a> >> <a href="reservas.php">Reservas</a> >> <strong>Detalle del recurso</strong>';
            return;
        }

        $presupuesto = Presupuesto::generar($this->usuarioId, $recursoId, $numPersonas, $recurso->getPrecio());

        if ($presupuesto === null) {
            $this->mensajeError = 'Error al generar el presupuesto. Intentalo de nuevo.';
            $this->accion       = 'detalle';
            $this->recurso      = $recurso;
            $this->breadcrumb   = '<a href="index.html">INICIO</a> >> <a href="reservas.php">Reservas</a> >> <strong>Detalle del recurso</strong>';
            return;
        }

        $this->presupuesto = $presupuesto;
        $this->recurso     = $recurso;
    }

    private function accionConfirmarReserva(): void {
        $this->breadcrumb   = '<a href="index.html">INICIO</a> >> <a href="reservas.php">Reservas</a> >> <strong>Confirmacion</strong>';
        $this->tituloPagina = 'Reserva confirmada | Turismo Valencia';

        if ($this->usuarioId === null) {
            header('Location: reservas.php?accion=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: reservas.php');
            exit;
        }

        $presupuestoId = (int)($_POST['presupuesto_id'] ?? 0);

        if ($presupuestoId <= 0) {
            $this->mensajeError = 'Datos del formulario no validos.';
            $this->accion       = 'inicio';
            $this->recursos     = Recurso::obtenerTodos();
            $this->breadcrumb   = '<a href="index.html">INICIO</a> >> <strong>Reservas</strong>';
            return;
        }

        $resultado = Reserva::confirmar($presupuestoId, $this->usuarioId);

        if ($resultado === true) {
            $this->mensajeExito = 'Reserva confirmada con exito. Puedes consultarla en Mis reservas.';
        } else {
            $this->mensajeError = $resultado;
        }
    }

    private function accionMisReservas(): void {
        $this->breadcrumb   = '<a href="index.html">INICIO</a> >> <a href="reservas.php">Reservas</a> >> <strong>Mis reservas</strong>';
        $this->tituloPagina = 'Mis reservas | Turismo Valencia';

        if ($this->usuarioId === null) {
            header('Location: reservas.php?accion=login');
            exit;
        }

        $this->reservas = Reserva::obtenerPorUsuario($this->usuarioId);
    }

    private function accionAnular(): void {
        if ($this->usuarioId === null) {
            header('Location: reservas.php?accion=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: reservas.php?accion=mis_reservas');
            exit;
        }

        $reservaId = (int)($_POST['reserva_id'] ?? 0);
        $resultado = Reserva::anular($reservaId, $this->usuarioId);

        $this->accion     = 'mis_reservas';
        $this->reservas   = Reserva::obtenerPorUsuario($this->usuarioId);
        $this->breadcrumb = '<a href="index.html">INICIO</a> >> <a href="reservas.php">Reservas</a> >> <strong>Mis reservas</strong>';
        $this->tituloPagina = 'Mis reservas | Turismo Valencia';

        if ($resultado === true) {
            $this->mensajeExito = 'Reserva anulada correctamente.';
        } else {
            $this->mensajeError = $resultado;
        }
    }

    private function renderizarPagina(): void {
        $titulo      = htmlspecialchars($this->tituloPagina);
        $breadcrumb  = $this->breadcrumb;
        $msgExito    = $this->mensajeExito;
        $msgError    = $this->mensajeError;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="author" content="Joaquin Jara Garcia UO300245">
    <meta name="description" content="Central de reservas de recursos turisticos de Valencia">
    <meta name="keywords" content="reservas, turismo, valencia, actividades, rutas">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?></title>
    <link rel="stylesheet" href="../estilo/estilo.css">
    <link rel="stylesheet" href="../estilo/layout.css">
</head>
<body>
  <header>
     <h1><a href="../index.html">Turismo Valencia</a></h1>
  <nav>
    <ul>
      <li><a href="../index.html">Inicio</a></li>
      <li><a href="../gastronomia.html">Gastronomía</a></li>
      <li><a href="../rutas.html">Rutas</a></li>
      <li><a href="../meteorologia.html">Meteorología</a></li>
      <li><a href="../juego.html">Juego</a></li>
      <li><a href="reservas.php" class="activo">Reservas</a></li>
      <li><a href="../ayuda.html">Ayuda</a></li>
    </ul>
  </nav>
</header>
 <p>Estas en: <?= $breadcrumb ?></p>
<main>

    <?php if ($msgExito): ?>
        <p><strong>Aviso:</strong> <?= htmlspecialchars($msgExito) ?></p>
    <?php endif; ?>
    <?php if ($msgError): ?>
        <p><strong>Error:</strong> <?= htmlspecialchars($msgError) ?></p>
    <?php endif; ?>

    <?php
        switch ($this->accion) {
            case 'registro':            $this->vistaRegistro();      break;
            case 'login':               $this->vistaLogin();         break;
            case 'detalle':             $this->vistaDetalle();       break;
            case 'generar_presupuesto': $this->vistaPresupuesto();   break;
            case 'confirmar_reserva':   $this->vistaConfirmacion();  break;
            case 'mis_reservas':        $this->vistaMisReservas();   break;
            default:                    $this->vistaInicio();
        }
    ?>
</main>
</body>
</html>
<?php
    }

    private function vistaInicio(): void {
        $logueado   = $this->usuarioId !== null;
        $nombreUser = htmlspecialchars($this->usuarioNombre ?? '');
?>
    <section>
        <h2>Central de Reservas Turisticas de Valencia</h2>
        <?php if ($logueado): ?>
            <p>Bienvenido/a, <strong><?= $nombreUser ?></strong>. |
               <a href="reservas.php?accion=mis_reservas">Ver mis reservas</a> |
               <a href="reservas.php?accion=logout">Cerrar sesion</a>
            </p>
        <?php else: ?>
            <p>
                <a href="reservas.php?accion=login">Iniciar sesion</a> |
                <a href="reservas.php?accion=registro">Registrarse</a>
            </p>
        <?php endif; ?>
    </section>

    <section>
        <h2>Recursos Turisticos Disponibles</h2>
        <?php if (empty($this->recursos)): ?>
            <p>No hay recursos turisticos disponibles en este momento.</p>
        <?php else: ?>
            <table>
                <caption>Listado de recursos turisticos reservables en Valencia</caption>
                <thead>
                    <tr>
                        <th scope="col">Tipo</th>
                        <th scope="col">Recurso</th>
                        <th scope="col">Precio/persona</th>
                        <th scope="col">Plazas libres</th>
                        <th scope="col">Disponible desde</th>
                        <th scope="col">Hasta</th>
                        <th scope="col">Accion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->recursos as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r->getTipoNombre()) ?></td>
                        <td>
                            <a href="reservas.php?accion=detalle&amp;id=<?= $r->getId() ?>">
                                <?= htmlspecialchars($r->getNombre()) ?>
                            </a>
                        </td>
                        <td><?= number_format($r->getPrecio(), 2) ?> &euro;</td>
                        <td><?= $r->getPlazasDisponibles() ?> / <?= $r->getPlazasTotales() ?></td>
                        <td><?= htmlspecialchars($r->getFechaInicio()) ?></td>
                        <td><?= htmlspecialchars($r->getFechaFin()) ?></td>
                        <td>
                            <?php if ($r->tieneDisponibilidad()): ?>
                                <a href="reservas.php?accion=detalle&amp;id=<?= $r->getId() ?>">Reservar</a>
                            <?php else: ?>
                                <span>Sin plazas</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
<?php
    }

    private function vistaRegistro(): void {
?>
    <section>
        <h2>Crear cuenta</h2>
        <p>Registrate para poder realizar reservas de actividades turisticas.</p>
        <form method="post" action="reservas.php?accion=registro">
            <p>
                <label for="reg-nombre">Nombre</label>
                <input type="text" id="reg-nombre" name="nombre" required maxlength="100">
            </p>
            <p>
                <label for="reg-apellidos">Apellidos</label>
                <input type="text" id="reg-apellidos" name="apellidos" required maxlength="150">
            </p>
            <p>
                <label for="reg-email">Correo electronico</label>
                <input type="email" id="reg-email" name="email" required maxlength="100">
            </p>
            <p>
                <label for="reg-password">Contrasena (minimo 6 caracteres)</label>
                <input type="password" id="reg-password" name="password" required minlength="6">
            </p>
            <p>
                <label for="reg-password2">Repetir contrasena</label>
                <input type="password" id="reg-password2" name="password2" required minlength="6">
            </p>
            <p>
                <button type="submit">Registrarse</button>
            </p>
        </form>
        <p>Ya tienes cuenta? <a href="reservas.php?accion=login">Inicia sesion</a></p>
    </section>
<?php
    }

    private function vistaLogin(): void {
?>
    <section>
        <h2>Iniciar sesion</h2>
        <p>Accede con tu cuenta para realizar o consultar reservas.</p>
        <form method="post" action="reservas.php?accion=login">
            <p>
                <label for="log-email">Correo electronico</label>
                <input type="email" id="log-email" name="email" required maxlength="100">
            </p>
            <p>
                <label for="log-password">Contrasena</label>
                <input type="password" id="log-password" name="password" required>
            </p>
            <p>
                <button type="submit">Acceder</button>
            </p>
        </form>
        <p>No tienes cuenta? <a href="reservas.php?accion=registro">Registrate gratis</a></p>
    </section>
<?php
    }

    private function vistaDetalle(): void {
        $r        = $this->recurso;
        $logueado = $this->usuarioId !== null;

        if ($r === null) return;
?>
    <section>
        <h2><?= htmlspecialchars($r->getNombre()) ?></h2>
        <p><strong>Tipo:</strong> <?= htmlspecialchars($r->getTipoNombre()) ?></p>
        <p><?= htmlspecialchars($r->getDescripcion()) ?></p>
    </section>

    <section>
        <h2>Informacion y disponibilidad</h2>
        <table>
            <caption>Detalles del recurso turistico</caption>
            <tbody>
                <tr>
                    <th scope="row">Precio por persona</th>
                    <td><?= number_format($r->getPrecio(), 2) ?> &euro;</td>
                </tr>
                <tr>
                    <th scope="row">Plazas disponibles</th>
                    <td><?= $r->getPlazasDisponibles() ?> de <?= $r->getPlazasTotales() ?></td>
                </tr>
                <tr>
                    <th scope="row">Disponible desde</th>
                    <td><?= htmlspecialchars($r->getFechaInicio()) ?></td>
                </tr>
                <tr>
                    <th scope="row">Disponible hasta</th>
                    <td><?= htmlspecialchars($r->getFechaFin()) ?></td>
                </tr>
            </tbody>
        </table>
    </section>

    <?php if (!$r->tieneDisponibilidad()): ?>
    <section>
        <p>Este recurso no tiene plazas disponibles.</p>
    </section>
    <?php elseif (!$logueado): ?>
        <p>Debes <a href="reservas.php?accion=login">iniciar sesion</a> o
           <a href="reservas.php?accion=registro">registrarte</a> para reservar.</p>
    <?php else: ?>
    <section>
        <h2>Generar presupuesto</h2>
        <p>Introduce el numero de personas para calcular el presupuesto antes de confirmar.</p>
        <form method="post" action="reservas.php?accion=generar_presupuesto">
            <input type="hidden" name="recurso_id" value="<?= $r->getId() ?>">
            <p>
                <label for="num_personas">Numero de personas (max. <?= $r->getPlazasDisponibles() ?>)</label>
                <input type="number" id="num_personas" name="num_personas" min="1" max="<?= $r->getPlazasDisponibles() ?>" value="1" required>
            </p>
            <p>
                <button type="submit">Calcular presupuesto</button>
            </p>
        </form>
    </section>
    <?php endif; ?>
    <p><a href="reservas.php">&laquo; Volver al listado</a></p>
<?php
    }

    private function vistaPresupuesto(): void {
        $pres = $this->presupuesto;
        $r    = $this->recurso;

        if ($pres === null || $r === null) return;
?>
    <section>
        <h2>Resumen del presupuesto</h2>
        <p>Revisa los detalles. Si estas de acuerdo, pulsa Confirmar para finalizar la reserva.</p>

        <table>
            <caption>Detalle economico del presupuesto</caption>
            <tbody>
                <tr>
                    <th scope="row">Recurso turistico</th>
                    <td><?= htmlspecialchars($r->getNombre()) ?></td>
                </tr>
                <tr>
                    <th scope="row">Tipo</th>
                    <td><?= htmlspecialchars($r->getTipoNombre()) ?></td>
                </tr>
                <tr>
                    <th scope="row">Disponibilidad</th>
                    <td><?= htmlspecialchars($r->getFechaInicio()) ?> &mdash; <?= htmlspecialchars($r->getFechaFin()) ?></td>
                </tr>
                <tr>
                    <th scope="row">Numero de personas</th>
                    <td><?= $pres->getNumPersonas() ?></td>
                </tr>
                <tr>
                    <th scope="row">Precio por persona</th>
                    <td><?= number_format($pres->getPrecioUnitario(), 2) ?> &euro;</td>
                </tr>
                <tr>
                    <th scope="row">Precio total</th>
                    <td><strong><?= number_format($pres->getPrecioTotal(), 2) ?> &euro;</strong></td>
                </tr>
                <tr>
                    <th scope="row">Fecha de generacion</th>
                    <td><?= htmlspecialchars($pres->getFechaGeneracion()) ?></td>
                </tr>
            </tbody>
        </table>
    </section>

    <section>
        <form method="post" action="reservas.php?accion=confirmar_reserva">
            <input type="hidden" name="presupuesto_id" value="<?= $pres->getId() ?>">
            <p>
                <button type="submit">Confirmar reserva</button>
                <a href="reservas.php?accion=detalle&amp;id=<?= $r->getId() ?>">Cancelar</a>
            </p>
        </form>
    </section>
<?php
    }

    private function vistaConfirmacion(): void {
?>
    <section>
        <h2>Estado de la reserva</h2>
        <?php if ($this->mensajeExito): ?>
            <p>Consulta el detalle en <a href="reservas.php?accion=mis_reservas">Mis reservas</a>.</p>
        <?php endif; ?>
        <p><a href="reservas.php">&laquo; Volver al listado de recursos</a></p>
    </section>
<?php
    }

    private function vistaMisReservas(): void {
        $nombreUser = htmlspecialchars($this->usuarioNombre ?? '');
?>
    <section>
        <h2>Mis reservas</h2>
        <p>Reservas de <strong><?= $nombreUser ?></strong>.
           <a href="reservas.php?accion=logout">Cerrar sesion</a></p>

        <?php if (empty($this->reservas)): ?>
            <p>No tienes ninguna reserva. <a href="reservas.php">Explorar recursos</a>.</p>
        <?php else: ?>
            <table>
                <caption>Historial de reservas del usuario</caption>
                <thead>
                    <tr>
                        <th scope="col">N&ordm;</th>
                        <th scope="col">Recurso</th>
                        <th scope="col">Personas</th>
                        <th scope="col">Total pagado</th>
                        <th scope="col">Fecha reserva</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Accion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->reservas as $res): ?>
                    <tr>
                        <td><?= $res->getId() ?></td>
                        <td><?= htmlspecialchars($res->getRecursoNombre()) ?></td>
                        <td><?= $res->getNumPersonas() ?></td>
                        <td><?= number_format($res->getPrecioTotal(), 2) ?> &euro;</td>
                        <td><?= htmlspecialchars($res->getFechaReserva()) ?></td>
                        <td><?= htmlspecialchars($res->getEstado()) ?></td>
                        <td>
                            <?php if ($res->estaConfirmada()): ?>
                                <form method="post" action="reservas.php?accion=anular">
                                    <input type="hidden" name="reserva_id" value="<?= $res->getId() ?>">
                                    <button type="submit">Anular</button>
                                </form>
                            <?php else: ?>
                                <span>Anulada</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <p><a href="reservas.php">&laquo; Volver al listado</a></p>
<?php
    }
}

$controlador = new Controlador();
$controlador->ejecutar();