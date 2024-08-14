<?php
/**
 * @author Ron van der Molen <ron@gogento.com>
 */
namespace GoGentoOSS\SAMLBase\Binding;

/**
 * Class Redirect
 *
 * POST binding that uses HTTP-POST as a transport for a SAML request
 *
 * @package GoGentoOSS\SAMLBase\Binding
 */
class Artifact extends BindingAbstract
{
    /**
     * The location in the metadata that has the current bindings information
     * @var string
     */
    protected $metadataBindingLocation = 'ArtifactResolutionService';

    /**
     * Do a request with the current binding
     */
    public function resolveArtifact($artifact = '')
    {
        $this->setTargetUrlFromMetadata($this->metadataBindingLocation);
        $this->setProtocolBinding(self::BINDING_POST);

        $this->getSettings()->setValue('artifact', $artifact);
        
        $soapRequest = $this->buildEnvelope('ArtifactResolve');

        $request = $this->getHttpService()->post($this->getTargetUrl(), null, $soapRequest);
        $response = $request->send();

        return (string) $response->getBody();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function sendArtifact()
    {
        $this->setTargetUrlFromMetadata($this->metadataBindingLocation);

        $this->setProtocolBinding(self::BINDING_ARTIFACT);

        $targetUrl = (string)$this->getTargetUrl() . '?SAMLart=' . $this->generateArtifact() . '&RelayState=' . mt_rand(0,1000000);

        header('Location: ' . $targetUrl);
        exit;
    }

    /**
     * @param $requestType
     * @return mixed
     */
    protected function buildEnvelope($requestType = 'ArtifactResolve')
    {
        $requestTemplate = $this->getTwigService()->render($requestType . '.xml.twig',
            array_merge($this->getSettings()->getValues(), array(
                'ProtocolBinding' => $this->getProtocolBinding(),
                'UniqueID' => $this->getUniqueIdService()->generate(),
                'Timestamp' => $this->getTimestampService()->generate()->toFormat(),
            ))
        );

        $signedTemplate = $this->signTemplate($requestTemplate);

        $signedTemplate = str_replace('<?xml version="1.0"?>', '', $signedTemplate);
        return $this->getTwigService()->render('SoapEnvelope.xml.twig',
            array('SAMLContent' => $signedTemplate)
        );
    }
}
