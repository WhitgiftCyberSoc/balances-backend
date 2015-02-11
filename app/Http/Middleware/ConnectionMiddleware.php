<?php namespace App\Http\Middleware;

use Closure;

class ConnectionMiddleware {

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		// Validate HTTPS connection
		if (!($request->secure())) {
			return response()->json(['error' => 'true', 'message' => 'The server has rejected an insecure connection.'], 400);
		}

		// Validate Content-Type
		if ($request->header('Content-Type') != 'application/x-www-form-urlencoded') {
			return response()->json(['error' => 'true', 'message' => 'The server has rejected an improper request header.'], 400);
		}

		return $next($request);
	}

}
