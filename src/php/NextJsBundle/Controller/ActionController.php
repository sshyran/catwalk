<?php

namespace Frontastic\Catwalk\NextJsBundle\Controller;

use Frontastic\Catwalk\ApiCoreBundle\Domain\Hooks\HooksService;
use Frontastic\Catwalk\NextJsBundle\Domain\Api\Request;
use Frontastic\Catwalk\NextJsBundle\Domain\RequestService;
use Frontastic\Catwalk\NextJsBundle\Domain\Api\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ActionController
{
    private HooksService $hooksService;
    private HttpKernelInterface $httpKernel;
    private string $rootDir;
    private RequestService $requestService;

    public function __construct(
        HooksService $hooksService,
        HttpKernelInterface $httpKernel,
        RequestService $requestService,
        string $rootDir
    ) {
        $this->hooksService = $hooksService;
        $this->httpKernel = $httpKernel;
        $this->requestService = $requestService;
        $this->rootDir = $rootDir;
    }

    public function indexAction(string $namespace, string $action, SymfonyRequest $request): JsonResponse
    {

        if ($this->hasOverride($namespace, $action)) {
            return $this->performOverrideForward($namespace, $action, $request);
        }

        $hookName = sprintf('action-%s-%s', $namespace, $action);

        $requestSessionData = null;

        if ($request->cookies->get('frontastic-session')) {
            $requestSessionData = $this->requestService->decodeAndValidateJWTSessionToken(
                $request->cookies->get('frontastic-session')
            );
        }

        $apiRequest = $this->requestService->createApiRequest($request, $requestSessionData);

        /** @var stdClass $apiResponse */
        $apiResponse = $this->hooksService->call($hookName, [$apiRequest]);

        if (isset($apiResponse->sessionData)) {
            $responseSessionData = $apiResponse->sessionData;
        } else {
            $responseSessionData = $requestSessionData;
        }

        $response = new JsonResponse();
        if (isset($apiResponse->ok) && !$apiResponse->ok) {
            // hooksservice signaled an error
            $response->setStatusCode(500);
            $response->setContent(json_encode($apiResponse, JSON_FORCE_OBJECT));
        } elseif (!isset($apiResponse->statusCode) || !isset($apiResponse->body)) {
            // response from extension is not in the expected form (which is a Response object)
            $response->setStatusCode(200);
            $response->headers->setCookie(
                new Cookie('frontastic-session', $this->requestService->encodeJWTData($responseSessionData))
            );
            $response->headers->set(
                'X-Extension-Error',
                'Data returned from hook did not have statusCode or body fields'
            );
            $response->setContent(json_encode($apiResponse, JSON_FORCE_OBJECT));
            /* XXX
               if the reponse from the extension is no Response object, it
                   should error, but for the TT release we just pass it along.
            $response->setStatusCode(500);
            $response->setData(
                [
                    'ok' => false,
                    'message' => "Data returned from hook did not have statusCode or body fields"
                ]
            );
            */
        } else {
            $response->headers->setCookie(
                new Cookie('frontastic-session', $this->requestService->encodeJWTData($responseSessionData))
            );
            $response->setContent($apiResponse->body);
            $response->setStatusCode($apiResponse->statusCode);
            // TODO pass other headers to JsonResponse
        }

        return $response;
    }

    private function performOverrideForward(string $namespace, string $action, SymfonyRequest $request): SymfonyResponse
    {
        $overrides = $this->getOverrides();
        $controller = $overrides[$namespace][$action];

        $subRequest = $request->duplicate(null, null, ['_controller' => $controller]);

        return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    private function hasOverride(string $namespace, string $action): bool
    {
        $overrides = $this->getOverrides();

        return (isset($overrides[$namespace]) && isset($overrides[$namespace][$action]));
    }

    private function getOverrides(): array
    {
        $overrideFile = sprintf('%s/config/action_override.json', $this->rootDir);

        if (!file_exists($overrideFile)) {
            return [];
        }

        return json_decode(file_get_contents($overrideFile), true);
    }
}
