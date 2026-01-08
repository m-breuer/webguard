<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Monitoring;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class PublicLabelController
 *
 * This controller is responsible for handling requests for public monitoring labels.
 * It retrieves all necessary monitoring data using the ApiController and displays it on a public page.
 */
class PublicLabelController extends Controller
{
    protected ApiController $apiController;

    /**
     * Create a new controller instance.
     *
     * @param  ApiController  $apiController  The internal API controller instance.
     */
    public function __construct(ApiController $apiController)
    {
        $this->apiController = $apiController;
    }

    /**
     * Handle the incoming request to display a public monitoring label.
     *
     * @param  Monitoring  $monitoring  The monitoring model
     * @param  Request  $request  The HTTP request instance.
     * @return View The view displaying the public monitoring label.
     */
    public function __invoke(Monitoring $monitoring, Request $request): View
    {
        abort_unless($monitoring->public_label_enabled, 404);

        return view('monitorings.public-label', [
            'monitoring' => $monitoring,
        ]);
    }
}
