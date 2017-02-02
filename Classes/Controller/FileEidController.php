<?php
namespace KoninklijkeCollective\KoningSecuredFiles\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * EidController: File handling
 *
 * @package KoninklijkeCollective\KoningSecuredFiles\Controller
 */
class FileEidController
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function processSecuredFile(ServerRequestInterface $request, ResponseInterface $response)
    {
        $file = $this->getParameter($request, 'file');
        try {
            if ($file = $this->getSecuredFileService()->getFileObject($file)) {
                // Prepare response with given body content
                $response->getBody()->write($file->getContents());
                return $response
                    ->withAddedHeader('Pragma', 'private')
                    ->withAddedHeader('Expires', '0')
                    ->withAddedHeader('Cache-Control', 'must-revalidate')
                    ->withAddedHeader('Content-Type', $file->getMimeType())
                    ->withAddedHeader('Content-Disposition', 'inline; filename="' . $file->getName() . '"');
            }
        } catch (\Exception $exception) {
            // Prepare response with given body content
            $response->getBody()->write($exception->getMessage());
            return $response->withStatus(404);
        }
        // Prepare response with given body content
        $response->getBody()->write('Unknown error');
        return $response->withStatus(410);
    }

    /**
     * Get given query parameter
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param string $query
     * @param string $default
     * @return string
     */
    protected function getParameter(ServerRequestInterface $request, $query, $default = '')
    {
        return isset($request->getParsedBody()[$query]) ? $request->getParsedBody()[$query] :
            (isset($request->getQueryParams()[$query]) ? $request->getQueryParams()[$query] : $default);
    }

    /**
     * @return \KoninklijkeCollective\KoningSecuredFiles\Service\SecuredFileService
     */
    protected function getSecuredFileService()
    {
        return $this->getObjectManager()->get(\KoninklijkeCollective\KoningSecuredFiles\Service\SecuredFileService::class);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected function getObjectManager()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
    }
}
