<?php
require_once __DIR__ . '/../includes/db.php';

$action = $_GET['action'] ?? '';

/* GET */
if ($action === 'get') {

    $stmt = $pdo->prepare("SELECT * FROM horarios WHERE id=?");
    $stmt->execute([$_GET['id']]);

    header('Content-Type: application/json');
    echo json_encode($stmt->fetch());
    exit;
}

/* DELETE */
if ($action === 'delete') {

    $stmt = $pdo->prepare("DELETE FROM horarios WHERE id=?");
    $stmt->execute([$_GET['id']]);
    exit;
}

/* SAVE */
if ($action === 'save') {
    depurar("Guardando horario...".
        " id: " . ($_POST['id'] ?? 'nuevo').
        " profesor_id: " . $_POST['profesor_id'].
        " tipo: " . $_POST['tipo'].
        " dia_semana: " . $_POST['dia_semana'].
        " tramo_horario: " . $_POST['tramo_horario'].
        " asignatura: " . ($_POST['asignatura'] ?? '').
        " grupo: " . ($_POST['grupo'] ?? '').
        " aula: " . ($_POST['aula'] ?? '')
    );    
    $id = $_POST['id'] ?? null;
    $profesor_id = $_POST['profesor_id'];
    $tipo = $_POST['tipo'];
    $dia = $_POST['dia_semana'];
    $tramo = $_POST['tramo_horario'];

    $asignatura = $_POST['asignatura'] ?? null;
    $grupo = $_POST['grupo'] ?? null;
    $aula = $_POST['aula'] ?? null;

    if ($tipo === 'Guardias') {
        $asignatura = '';
        $grupo = '';
        $aula = '';
    }

    if ($id) {
        $stmt = $pdo->prepare("
            UPDATE horarios SET
                tipo=?, asignatura=?, grupo=?, aula=?,
                dia_semana=?, tramo_horario=?
            WHERE id=?
        ");

        $stmt->execute([
            $tipo, $asignatura, $grupo, $aula,
            $dia, $tramo, $id
        ]);

    } else {
        $stmt = $pdo->prepare("
            INSERT INTO horarios
            (profesor_id, tipo, asignatura, grupo, aula, dia_semana, tramo_horario)
            VALUES (?,?,?,?,?,?,?)
        ");

        $stmt->execute([
            $profesor_id, $tipo, $asignatura, $grupo, $aula,
            $dia, $tramo
        ]);
    }

    exit;
}