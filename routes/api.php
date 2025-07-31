use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\NegocioController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\CitaController;

Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('negocios', NegocioController::class);
Route::apiResource('servicios', ServicioController::class);
Route::apiResource('citas', CitaController::class);

// Ejemplo de ruta con scopes
Route::get('citas/estado/{estado}', [CitaController::class, 'filtrarPorEstado']);

Route::get('servicios/categoria/{categoria}', function($categoria) {
    return App\Models\Servicio::categoria($categoria)->get();
});

Route::get('servicios/negocio/{negocio_id}', function($negocio_id) {
    return App\Models\Servicio::negocio($negocio_id)->get();
});

Route::get('usuarios/rol/{rol}', function($rol) {
    return App\Models\Usuario::rol($rol)->get();
});
