<?php
/**
 * @author Ron van der Molen <ron@gogento.com>
 *
 * For the example we manually build a container with the required settings
 * 
 */

$container = new Symfony\Component\DependencyInjection\ContainerBuilder();

$container->register('twig_loader', 'Twig_Loader_Filesystem')->addArgument('../src/GoGentoOSS/SAMLBase/Template/Twig');
$container->register('twig', 'Twig_Environment')->addArgument(new Symfony\Component\DependencyInjection\Reference('twig_loader'));

$container->register('guzzle_http', 'Guzzle\Http\Client');

$container->register('SigningCertificate', 'GoGentoOSS\SAMLBase\Certificate')
    ->addMethodCall('setPassphrase', array('test1234'))
    ->addMethodCall('setPublicKey', array('cert/example.crt', true))
    ->addMethodCall('setPrivateKey', array('cert/example.pem', true));

$container->register('EncryptionCertificate', 'GoGentoOSS\SAMLBase\Certificate')
    ->addMethodCall('setPassphrase', array('test1234'))
    ->addMethodCall('setPublicKey', array('./cert/example.crt', true))
    ->addMethodCall('setPrivateKey', array('./cert/example.pem', true));

$container->register('samlbase_idp_settings', 'GoGentoOSS\SAMLBase\Configuration\Settings')
    ->addMethodCall('setValues', 
        array(
            array(
                'NameID' => 'testNameId',
                'Issuer' => 'https://your.serviceprovider.nl/',
                'MetadataExpirationTime' => 604800,
                'SPReturnUrl' => 'http://your.serviceprovider.nl/response.php',
                'ForceAuthn' => 'true',
                'IsPassive' => 'false',
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
                'ComparisonLevel' => 'exact',
            )
        )
    );

$container->register('samlbase_encryption', 'GoGentoOSS\SAMLBase\Security\Encryption')
    ->addMethodCall('setCertificate',array(new Symfony\Component\DependencyInjection\Reference('EncryptionCertificate')));

$container->register('samlbase_signature', 'GoGentoOSS\SAMLBase\Security\Signature')
    ->addMethodCall('setCertificate',array(new Symfony\Component\DependencyInjection\Reference('SigningCertificate')));

$container->register('samlbase_unique_id_generator', 'GoGentoOSS\SAMLBase\Configuration\UniqueID');
$container->register('samlbase_timestamp_generator', 'GoGentoOSS\SAMLBase\Configuration\Timestamp');
/**
 * Setup the Metadata resolve service
 */
$container->register('resolver', 'GoGentoOSS\SAMLBase\Metadata\ResolveService')
    ->addArgument(new Symfony\Component\DependencyInjection\Reference('guzzle_http'));

$container->register('samlbase_metadata', 'GoGentoOSS\SAMLBase\Metadata\IDPMetadata');

/**
 * Resolve the metadata
 */
$metadata = $container->get('resolver')->resolve($container->get('samlbase_metadata'), 'https://your.identityprovider.nl/metadata/url');

// POST Binding
$container->register('samlbase_binding_post', 'GoGentoOSS\SAMLBase\Binding\Post')
    ->addMethodCall('setMetadata', array($metadata))
    ->addMethodCall('setTwigService', array(new Symfony\Component\DependencyInjection\Reference('twig')))
    ->addMethodCall('setUniqueIdService', array(new Symfony\Component\DependencyInjection\Reference('samlbase_unique_id_generator')))
    ->addMethodCall('setTimestampService', array(new Symfony\Component\DependencyInjection\Reference('samlbase_timestamp_generator')))
    ->addMethodCall('setSignatureService', array(new Symfony\Component\DependencyInjection\Reference('samlbase_signature')))
    ->addMethodCall('setHttpService', array(new Symfony\Component\DependencyInjection\Reference('guzzle_http')));

// OR Redirect Binding
$container->register('samlbase_binding_redirect', 'GoGentoOSS\SAMLBase\Binding\Redirect')
    ->addMethodCall('setMetadata', array($metadata))
    ->addMethodCall('setTwigService', array(new Symfony\Component\DependencyInjection\Reference('twig')))
    ->addMethodCall('setUniqueIdService', array(new Symfony\Component\DependencyInjection\Reference('samlbase_unique_id_generator')))
    ->addMethodCall('setTimestampService', array(new Symfony\Component\DependencyInjection\Reference('samlbase_timestamp_generator')))
    ->addMethodCall('setSignatureService', array(new Symfony\Component\DependencyInjection\Reference('samlbase_signature')))
    ->addMethodCall('setHttpService', array(new Symfony\Component\DependencyInjection\Reference('guzzle_http')));