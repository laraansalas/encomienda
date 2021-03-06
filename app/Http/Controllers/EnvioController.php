<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ClienteDestinatario;
use App\ClienteRemitente;
use App\Encomienda;
use Illuminate\Support\Facades\Auth;
use Laracasts\Flash\Flash;
use DB;
use App\Quotation;
use App\Mail\CodigoEncReceived;
use Illuminate\Support\Facades\Mail;


class EnvioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
    $formInput =  $this->validate($request,[

             'nombre' => 'required|max:255',
             'apellido'  => 'required|max:255',
             'dni' => 'required|max:255',
             'telefono' => 'required|max:255',
             'email' => 'required|max:255',
             'direccion' => 'required|max:255',

             'peso' => 'required|max:255',
             'tamaño' => 'required|max:255',
             'destino' => 'required|max:255',
             'pago' => 'required|max:255',
             'descripcion' => 'required|max:255',

             'nombre2' => 'required|max:255',
             'apellido2' => 'required|max:255',
             'dni2' => 'required|max:255',
             'telefono2' => 'required|max:255',
             'email2' => 'required|max:255',
             'direccion2' => 'required|max:255',
         ],[
             'nombre.required' => 'El campo nombre remitente es obligatorio',
             'apellido.required' => 'El campo apellido remitente es obligatorio',
             'dni.required' => 'El campo DNI remitente es obligatorio',
             'telefono.required' => 'El campo telefono remitente es obligatorio',
             'email.required' => 'El campo email remitente es obligatorio',
             'direccion.required' => 'El campo direccion remitente es obligatorio',

             'peso.required' => 'El campo peso es obligatorio',
             'tamaño.required' => 'El campo tamaño es obligatorio',
             'destino.required' => 'El campo destino es obligatorio',
             'pago.required' => 'El campo pago es obligatorio',
             'descripcion.required' => 'El campo descripcion es obligatorio',

             'nombre2.required' => 'El campo nombre destinatario es obligatorio',
             'apellido2.required' => 'El campo apellido destinatario es obligatorio',
             'dni2.required' => 'El campo DNI destinatario es obligatorio',
             'telefono2.required' => 'El campo telefono destinatario es obligatorio',
             'email2.required' => 'El campo email remitente es obligatorio',
             'direccion2.required' => 'El campo direccion destinatario es obligatorio',

          ]);

         $errores = null;
         DB::beginTransaction();
         try {

           $clienter_aux = ClienteRemitente::where('dni_clienter','=',$request->dni)  ->select('id') ->first();
           if (empty($clienter_aux))
           {
           $origen = New ClienteRemitente;
           $origen -> nombre_clienter = $request -> nombre;
           $origen -> apellido_clienter = $request -> apellido;
           $origen -> dni_clienter = $request -> dni;
           $origen -> telefono_clienter = $request -> telefono;
           $origen -> email_clienter = $request -> email;
           $origen -> direccion_clienter = $request -> direccion;
           $origen -> save();
           $rem = $origen -> id;
          }
          else {
            $rem=$clienter_aux -> id;
          }

          $cliente_aux = ClienteDestinatario::where('dni_cliente','=',$request->dni2)  ->select('id') ->first();
          if (empty($cliente_aux))
          {
           $destino = New ClienteDestinatario;
           $destino -> nombre_cliente = $request -> nombre2;
           $destino -> apellido_cliente = $request -> apellido2;
           $destino -> dni_cliente = $request -> dni2;
           $destino -> telefono_cliente = $request -> telefono2;
           $destino -> email_cliente = $request -> email2;
           $destino -> direccion_cliente = $request -> direccion2;
           $destino -> save();
           $dest = $destino -> id;
          }
          else {
            $dest=$cliente_aux -> id;
          }


           $encomienda = New Encomienda;
           $encomienda -> peso_encomienda = $request -> peso;
           $encomienda -> tamaño_encomienda = $request -> tamaño;
           $encomienda -> destino_encomienda = $request -> destino;
           $encomienda -> pago_encomienda = $request -> pago;
           $encomienda -> descripcion_encomienda = $request -> descripcion;
           $encomienda -> id_personal = Auth::user()->id;
           $encomienda -> id_clienteremitente = $rem;
           $encomienda -> id_clientedestinatario = $dest;
           $encomienda -> save();

	       DB::commit();
	       $success = true;
         } catch (\Exception $e) {
	          $success = false;
            $errores = $e->getMessage();
	       DB::rollback();
         return view('inicio', compact('errores'));
         }
         if ($success) {
           $destin = ClienteDestinatario::where('id','=',$encomienda-> id_clientedestinatario )  ->select('email_cliente','nombre_cliente','apellido_cliente') ->first();
           $remit = ClienteRemitente::where('id','=',$encomienda-> id_clienteremitente )  ->select('nombre_clienter','apellido_clienter') ->first();
           $objDemo = new \stdClass();
           $objDemo->datos = $encomienda -> id;
           $objDemo->datos2 = $remit-> nombre_clienter;
           $objDemo->datos3 = $remit-> apellido_clienter;
           $objDemo->sender = 'Integral Pack Express';
           $objDemo->receiver = $destin -> nombre_cliente;
           $objDemo->receiver2 = $destin -> apellido_cliente;
           Mail::to($destin->email_cliente)->send(new CodigoEncReceived($objDemo));
           return redirect('inicio');
         }

    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $encomienda = DB::table('encomiendas')
      ->join('clientesremitentes','clientesremitentes.id','=','encomiendas.id_clienteremitente')
      ->join('clientesdestinatarios','clientesdestinatarios.id','=','encomiendas.id_clientedestinatario')
      ->select('encomiendas.*',
               'clientesdestinatarios.nombre_cliente','clientesdestinatarios.apellido_cliente','clientesdestinatarios.dni_cliente','clientesdestinatarios.telefono_cliente','clientesdestinatarios.email_cliente','clientesdestinatarios.direccion_cliente',
               'clientesremitentes.nombre_clienter','clientesremitentes.apellido_clienter','clientesremitentes.dni_clienter','clientesremitentes.telefono_clienter','clientesremitentes.email_clienter','clientesremitentes.direccion_clienter')
      ->where('estado_encomienda','=',false)
      ->where('encomiendas.id','=',$id)
      ->get();
        return view('editar',compact('encomienda'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
      $errores = null;
      DB::beginTransaction();
      try {

        $origenid = Encomienda::where('id', '=', $id)->select('id_clienteremitente')->get();

        $origen = ClienteRemitente::find($origenid)->first();
        $origen -> nombre_clienter = $request -> nombre;
        $origen -> apellido_clienter = $request -> apellido;
        $origen -> dni_clienter = $request -> dni;
        $origen -> telefono_clienter = $request -> telefono;
        $origen -> email_clienter = $request -> email;
        $origen -> direccion_clienter = $request -> direccion;
        $origen -> save();


        $destinoid = Encomienda::where('id', '=', $id)->select('id_clientedestinatario')->get();

        $destino = ClienteDestinatario::find($destinoid)->first();
        $destino -> nombre_cliente = $request -> nombre2;
        $destino -> apellido_cliente = $request -> apellido2;
        $destino -> dni_cliente = $request -> dni2;
        $destino -> telefono_cliente = $request -> telefono2;
        $origen -> email_clienter = $request -> email2;
        $destino -> direccion_cliente = $request -> direccion2;
        $destino -> save();


        $encomienda = Encomienda::find($id);
        $encomienda -> peso_encomienda = $request -> peso;
        $encomienda -> tamaño_encomienda = $request -> tamaño;
        $encomienda -> destino_encomienda = $request -> destino;
        $encomienda -> pago_encomienda = $request -> pago;
        $encomienda -> descripcion_encomienda = $request -> descripcion;
        $encomienda -> id_personal = Auth::user()->id;
        $encomienda -> id_clienteremitente = $origen -> id;
        $encomienda -> id_clientedestinatario = $destino -> id;
        $encomienda -> save();

        DB::commit();
        $success = true;
        } catch (\Exception $e) {
           $success = false;
           $errores = $e->getMessage();
        DB::rollback();
        return view('inicio', compact('errores'));
        }
        if ($success) {
          return redirect()->action('EncomiendaController@index');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function entrega($id)
    // {
    //
    //   $encomiendas = Encomienda::find($id);
    //   $encomiendas -> estado_encomienda = true;
    //   $encomiendas ->save();
    //   dd($encomiendas);
    //   return redirect('entrega');
    // }

    public function buscarrem(Request $request){
      $aux = ClienteRemitente::where('dni_clienter','=', $request->valor ) ->get();
      return $aux;
      }

    public function buscardest(Request $request2){
        $aux = ClienteDestinatario::where('dni_cliente','=', $request2->valor2 ) ->get();
        return $aux;
        }


}
