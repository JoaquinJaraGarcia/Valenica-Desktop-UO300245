<?php
require_once __DIR__ . '/Bd.php';

/**
 * Clase Usuario - Gestion de usuarios del sistema de reservas.
 *
 * Encapsula todas las operaciones sobre la tabla 'usuarios':
 * registro, autenticacion y consulta de datos del usuario.
 * Las contrasenas se almacenan siempre con hash bcrypt mediante
 * password_hash(), nunca en texto plano.
 */
class Usuario {

    private int    $id;
    private string $nombre;
    private string $apellidos;
    private string $email;


    public function __construct(int $id, string $nombre, string $apellidos, string $email) {
        $this->id        = $id;
        $this->nombre    = $nombre;
        $this->apellidos = $apellidos;
        $this->email     = $email;
      
    }

    // ── Getters ────────────────────────────────────────────────
    public function getId():        int    { return $this->id; }
    public function getNombre():    string { return $this->nombre; }
    public function getApellidos(): string { return $this->apellidos; }
    public function getEmail():     string { return $this->email; }
    public function getNombreCompleto(): string {
        return $this->nombre . ' ' . $this->apellidos;
    }

    // ── Operaciones estaticas ──────────────────────────────────

    /**
     * Registra un nuevo usuario en la base de datos.
     *
     * @return true si se registra correctamente, string con el
     *         mensaje de error si el email ya existe u ocurre otro fallo.
     */
    public static function registrar(
        string $nombre,
        string $apellidos,
        string $email,
        string $password
    ): bool|string {

        $con = Bd::getInstancia()->getConexion();

        // Comprobar si el email ya esta registrado
        $stmt = $con->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            return 'El correo electronico ya esta registrado.';
        }
        $stmt->close();

        // Insertar con contrasena en hash bcrypt
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $con->prepare('INSERT INTO usuarios (nombre, apellidos, email, password) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $nombre, $apellidos, $email, $hash);

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        }

        $error = $stmt->error;
        $stmt->close();
        return 'Error al registrar el usuario: ' . $error;
    }

    /**
     * Autentica un usuario por email y contrasena.
     *
     * @return Usuario si las credenciales son correctas, null si no lo son.
     */
    public static function login(string $email, string $password): ?Usuario {
        $con  = Bd::getInstancia()->getConexion();
        $stmt = $con->prepare(
            'SELECT id, nombre, apellidos, email, password FROM usuarios WHERE email = ?'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $stmt->close();
            return null;
        }

        $fila = $result->fetch_assoc();
        $stmt->close();

        if (!password_verify($password, $fila['password'])) {
            return null;
        }

        return new Usuario(
            (int)$fila['id'],
            $fila['nombre'],
            $fila['apellidos'],
            $fila['email']
        );
    }

    /**
     * Devuelve un Usuario por su id, o null si no existe.
     */
    public static function obtenerPorId(int $id): ?Usuario {
        $con  = Bd::getInstancia()->getConexion();
        $stmt = $con->prepare(
            'SELECT id, nombre, apellidos, email FROM usuarios WHERE id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $stmt->close();
            return null;
        }

        $fila = $result->fetch_assoc();
        $stmt->close();

        return new Usuario(
            (int)$fila['id'],
            $fila['nombre'],
            $fila['apellidos'],
            $fila['email']
        );
    }
}
