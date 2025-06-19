export interface UserType {
    firstName: string;
    lastName: string;
    address: string;
    city: string;
    postcode: string;
    country: string;
    state?: string;
    phone: string;
    email: string;
}

export const USER_TYPES: Record<string, UserType> = {
    ES_USER: {
        firstName: 'Juan',
        lastName: 'Pérez',
        address: 'Calle Mayor 123',
        city: 'Madrid',
        postcode: '28001',
        country: 'ES',
        state: 'Madrid',
        phone: '+34612345678',
        email: 'juan.perez@example.com'
    },
    PT_USER: {
        firstName: 'João',
        lastName: 'Silva',
        address: 'Rua Augusta 456',
        city: 'Lisbon',
        postcode: '1100-053',
        country: 'PT',
        state: 'Lisboa',
        phone: '+351912345678',
        email: 'joao.silva@example.com'
    },
    US_USER: {
        firstName: 'John',
        lastName: 'Doe',
        address: '123 Main St',
        city: 'New York',
        state: 'NY',
        postcode: '10001',
        country: 'US',
        phone: '+12125551234',
        email: 'john.doe@example.com'
    }
};