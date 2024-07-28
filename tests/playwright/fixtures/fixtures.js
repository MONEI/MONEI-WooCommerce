const base = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const { admin } = require( '../test-data/data' );
const { random } = require( '../utils/helpers' );

exports.test = base.test.extend({
    wcApi: async ( { baseURL }, use ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
			axiosConfig: {
				// allow 404s, so we can check if a resource was deleted without try/catch
				validateStatus( status ) {
					return ( status >= 200 && status < 300 ) || status === 404;
				},
			},
		} );

		await use( api );
	},
	wcAdminApi: async ( { baseURL }, use ) => {
		const wcAdminApi = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc-admin', // Use wc-admin namespace
		} );

		await use( wcAdminApi );
	},
	wpApi: async ( { baseURL }, use ) => {
		const wpApi = await base.request.newContext( {
			baseURL,
			extraHTTPHeaders: {
				Authorization: `Basic ${ Buffer.from(
					`${ admin.username }:${ admin.password }`
				).toString( 'base64' ) }`,
				cookie: '',
			},
		} );

		await use( wpApi );
	},
	addToCart: async ( { wcApi }, use ) => {
		const addItemToCart = async ( productId, quantity = 1 ) => {
			const response = await wcApi.post( 'cart/add', {
				id: productId,
				quantity: quantity,
			} );

			if ( response.status !== 200 ) {
				throw new Error( `Failed to add item to cart: ${ response.data.message }` );
			}

			return response.data;
		};

		await use( addItemToCart );
	},
});