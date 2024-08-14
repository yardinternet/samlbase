<?php
/**
 * @author Ron van der Molen <ron@gogento.com>
 */
namespace GoGentoOSS\SAMLBase\Security;

use GoGentoOSS\SAMLBase\Certificate;
use \RobRichards\XMLSecLibs\XMLSecurityDSig;

class Signature extends XMLSecurityDSig implements SignatureInterface
{
    protected $certificate = null;
    protected $signingAlgorithm = '';

    /**
     *
     */
    public function __construct()
    {
        $this->setSigningAlgorithm(XMLSecurityDSig::SHA256);

	    return parent::__construct('ds');
    }

    /**
     * @param Certificate $certificate
     * @return void
     */
    public function setCertificate(Certificate $certificate)
    {
        $this->certificate = $certificate;
    }

    /**
     * @param $document
     * @return bool|int
     * @throws \Exception
     */
    public function verifyDOMDocument($document)
    {
        $signatureNode = $this->locateSignature($document);

        /**
         * No signature was added, it should not fail as this is not a requirement on redirect bindings
         */
        if (!$signatureNode) {
            return true;
        }

        $this->add509Cert($this->getCertificate()->getPublicKey()->getX509Certificate());
        $this->setCanonicalMethod(XMLSecurityDSig::EXC_C14N_COMMENTS);
        $this->addReference($document->documentElement, $this->signingAlgorithm, array('http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N), array('id_name' => 'ID'));

        return $this->verify($this->getCertificate()->getPublicKey());
    }

    /**
     * @return mixed|null
     */
    public function getCertificate()
    {
        return $this->certificate;
    }

    /**
     * @param $algorithm
     * @return void
     */
    public function setSigningAlgorithm($algorithm)
    {
        $this->signingAlgorithm = $algorithm;
    }

    /**
     * @return mixed|string
     */
    public function getSigningAlgorithm()
    {
        return $this->signingAlgorithm;
    }

    /**
     * @param $passphrase
     * @return void
     */
    public function setPassphrase($passphrase = '')
    {
        $this->passphrase = $passphrase;
    }

    /**
     * @return mixed|string
     */
    public function getPassphrase()
    {
        return $this->passphrase;
    }

    /**
     * Add the signature to the template
     *
     * @param \DOMElement $element
     * @return void
     * @throws \Exception
     */
    public function addSignature(\DOMDocument $document)
    {
        $this->signDocument($document, $document->firstChild->childNodes->item(2));
    }

    /**
     * Add the signature to the template
     *
     * @param \DOMElement $element
     * @return bool
     * @throws \Exception
     */
    public function signMetadata(\DOMDocument $document)
    {
        $this->signDocument($document, $document->firstChild->childNodes->item(1));
    }

    /**
     * Sign a SAML2 Document
     *
     * @param \DOMDocument $document
     * @param $node
     * @throws \Exception
     */
    protected function signDocument(\DOMDocument $document, $node)
    {
        $this->add509Cert($this->getCertificate()->getPublicKey()->getX509Certificate());
        $this->setCanonicalMethod(XMLSecurityDSig::EXC_C14N_COMMENTS);
        $this->addReference($document->documentElement, $this->signingAlgorithm, array('http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N), array('id_name' => 'ID'));

        $this->sign($this->getCertificate()->getPrivateKey());
        $this->insertSignature($document->firstChild, $node);
        $this->canonicalizeSignedInfo();
    }
}
