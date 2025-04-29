<?php

use App\Http\Resources\PortoResource;
use App\Http\Resources\TransactionResource;
use App\Models\Portofolio;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


// Auth
Route::post('/user/create', function (Request $request) {
    $request->validate([
        'name' => 'required|string',
        'username' => 'required|unique:users,username',
        'password' => 'required|min:3'
    ]);

    $request = $request->merge(['password' => bcrypt($request->password)]);

    $user = User::create($request->only(['name', 'username', 'password']));
    return response()->json(['message' => 'User created successfully', 'user' => $user]);
});


Route::post('/user/login', function (Request $request) {
    $request->validate([
        'username' => 'required',
        'password' => 'required'
    ]);

    $user = User::where('username', $request->username)->first();
    if (!$user || !password_verify($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }
    return response()->json(['message' => 'Login successful', 'token' => $user->createToken('authToken')->plainTextToken]);
});



Route::get('/user/data', function (Request $request) {
    return response()->json(['data' => $request->user()]);
})->middleware('auth:sanctum');


// Porto
Route::get('/portofolio/get', function (Request $request) {
    $user = $request->user();
    return response()->json(['data' => Portofolio::where('user_id', $user->id)->with(['transaction' => function ($query) {
        $query->orderBy('transaction_date', 'desc');
    }])->get()->toResourceCollection(PortoResource::class)]);
})->middleware('auth:sanctum');

Route::get('/portofolio/get/{porto_id}', function (Request $request, $porto_id) {
    $user = $request->user();
    $porto = Portofolio::where('user_id', $user->id)->where('id', $porto_id)->with(['transaction' => function ($query) {
        $query->orderBy('transaction_date', 'desc');
    }])->first();

    if (!$porto) {
        return response()->json(['error' => 'Portofolio not found'], 404);
    }

    return response()->json(['data' => $porto->toResource(PortoResource::class)]);
})->middleware('auth:sanctum');

Route::delete('/portofolio/delete/{porto_id}', function (Request $request, $porto_id) {
    $user = $request->user();

    $porto = Portofolio::where('user_id', $user->id)->where('id', $porto_id)->first();

    if (!$porto) {
        return response()->json(['error' => 'Portofolio not found'], 404);
    }
    $porto->delete();
    return response()->json(['message' => 'Portofolio deleted']);
})->middleware('auth:sanctum');

Route::post('/portofolio/update/{porto_id}', function (Request $request, $porto_id) {
    $request->validate([
        'title' => 'required',
        'interest_rate' => 'numeric'
    ]);

    $porto = Portofolio::where('user_id', $request->user()->id)->where('id', $porto_id)->first();
    if (!$porto) {
        return response()->json(['error' => 'Portofolio not found'], 404);
    }
    $porto->update([
        'title' => $request->input('title'),
        'description' => $request->input('description'),
        'interest_rate' => $request->input('interest_rate')
    ]);
    return response()->json(['message' => 'Portoofoli updated', 'portofolio' => $porto]);
})->middleware('auth:sanctum');

Route::post('/portofolio/create', function (Request $request) {
    $request->validate([
        'title' => 'required',
        'interest_rate' => 'numeric'
    ]);

    $user = Auth::user();
    $request = $request->mergeIfMissing(['user_id' => $user->id]);

    $portofolio = Portofolio::create($request->only(['title', 'description', 'interest_rate', 'user_id']));

    return response()->json(['message' => 'Portoofoli created', 'portofolio' => $portofolio]);
})->middleware('auth:sanctum');

Route::post('/transaction/create/{porto_id}', function (Request $request, $porto_id) {

    $request->validate([
        'title' => 'required',
        'amount' => 'required',
        'transaction_date' => 'required',
    ]);

    $user = $request->user();

    $portofolio = Portofolio::find($porto_id);
    if (!$portofolio) {
        return response()->json(['message' => 'Portofooli not found'], 404);
    }
    $transaction = Transaction::create([
        'user_id' => $user->id,
        'portfolio_id' => $porto_id,
        'title' => $request->input('title'),
        'amount' => $request->input('amount'),
        'transaction_date' => $request->input('transaction_date')
    ]);

    return response()->json(['message' => 'Transaction created', 'transaction' => $transaction]);
})->middleware('auth:sanctum');

Route::delete('transaction/delete/{transaction_id}', function (Request $request, $transaction_id) {
    $user = $request->user();
    $transaction = Transaction::where('id', $transaction_id)->where('user_id', $user->id)->first();
    if (!$transaction) {
        return response()->json(['message' => 'Transaction not found'], 404);
    }
    $transaction->delete();
    return response()->json(['message' => 'Transaction deleted']);
})->middleware('auth:sanctum');
