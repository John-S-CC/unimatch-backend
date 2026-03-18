<?php

require_once __DIR__ . '/../servicios/MotorPermutas.php';

class MotorEventos {

    public static function procesarEvento($conn, $tipoEvento, $datos = []){

        switch($tipoEvento){

            case "cancelacion_materia":

                self::eventoCancelacion($conn, $datos);

            break;

            case "nueva_solicitud":

                self::eventoNuevaSolicitud($conn, $datos);

            break;

            case "matricula_nueva":

                self::eventoMatricula($conn, $datos);

            break;

        }

        // después de cualquier evento revisamos permutas
        MotorPermutas::procesar($conn);

    }

    private static function eventoCancelacion($conn,$datos){

        // aquí solo registramos el evento
        // la liberación de cupo ocurre en la API
    }

    private static function eventoNuevaSolicitud($conn,$datos){

        // se podría registrar en una tabla de eventos
    }

    private static function eventoMatricula($conn,$datos){

        // nuevo cupo ocupado
    }

}