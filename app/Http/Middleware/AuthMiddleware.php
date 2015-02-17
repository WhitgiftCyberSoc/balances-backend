<?php namespace App\Http\Middleware;

use Closure;

class AuthMiddleware {

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		// Retrieve and validate credentials from request
		$username = $request->input('username');
		$password = $request->input('password');

		if (empty($username)) {
			return response()->json(['error' => 'true', 'message' => 'The username or password is missing.'], 401)->header('WWW-Authenticate', 'Basic realm="fake"');
		}

		if (empty($password)) {
			return response()->json(['error' => 'true', 'message' => 'The username or password is missing.'], 401)->header('WWW-Authenticate', 'Basic realm="fake"');
		}

		return $next($request);
	}

}
