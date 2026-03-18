<?php

class SolicitudesRepositorio {

    public static function pendientes($conn){

        $sql="SELECT * FROM solicitudes WHERE estado='pendiente'";

        $result=$conn->query($sql);

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function buscarPermutaGrupo($conn,$sol){

        $sql="
        SELECT *
        FROM solicitudes
        WHERE tipo_solicitud='cambio_grupo'
        AND grupo_origen=?
        AND grupo_destino=?
        AND estado='pendiente'
        LIMIT 1
        ";

        $stmt=$conn->prepare($sql);

        $stmt->bind_param(
            "ii",
            $sol['grupo_destino'],
            $sol['grupo_origen']
        );

        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public static function buscarPermutaMateria($conn,$sol){

        $sql="
        SELECT *
        FROM solicitudes
        WHERE tipo_solicitud='cambio_materia'
        AND materia_origen=?
        AND materia_destino=?
        AND estado='pendiente'
        LIMIT 1
        ";

        $stmt=$conn->prepare($sql);

        $stmt->bind_param(
            "ii",
            $sol['materia_destino'],
            $sol['materia_origen']
        );

        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public static function marcarPermuta($conn,$id1,$id2){

        $sql="
        UPDATE solicitudes
        SET estado='permuta'
        WHERE id_solicitud IN (?,?)
        ";

        $stmt=$conn->prepare($sql);

        $stmt->bind_param("ii",$id1,$id2);

        $stmt->execute();
    }

    public static function marcarCompletada($conn,$id){

        $sql="
        UPDATE solicitudes
        SET estado='completada'
        WHERE id_solicitud=?
        ";

        $stmt=$conn->prepare($sql);

        $stmt->bind_param("i",$id);

        $stmt->execute();
    }

    public static function buscarPorOrigen($conn,$materia){

        $sql="
        SELECT *
        FROM solicitudes
        WHERE materia_origen=?
        AND estado='pendiente'
        ";

        $stmt=$conn->prepare($sql);

        $stmt->bind_param("i",$materia);

        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}