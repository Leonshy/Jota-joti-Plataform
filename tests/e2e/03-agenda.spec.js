import { expect, test } from '@playwright/test';
import { sesionDe } from './apoyo.mjs';
import { tinker } from './entorno.mjs';

test.describe('Agenda y reservas', () => {
    test('reservar, ver en «Mis reservas» y cancelar con confirmación', async ({ browser }) => {
        const { page } = await sesionDe(browser, '4000013');

        await page.goto('/app/actividades');
        const taller = page.getByRole('region', { name: 'Taller de primeros auxilios' });
        await taller.getByRole('button', { name: /^Apuntarme/ }).first().click();
        await expect(page.getByText(/Te apuntaste a/)).toBeVisible();
        await expect(taller.getByText('Apuntado').first()).toBeVisible();

        await page.getByRole('link', { name: 'Mis reservas' }).click();
        await expect(page.getByText('Taller de primeros auxilios')).toBeVisible();

        await page.getByRole('button', { name: 'Cancelar reserva' }).click();
        await expect(page.getByText('¿Cancelar la reserva?')).toBeVisible();
        await page.getByRole('button', { name: 'No, la dejo' }).click();
        await expect(page.getByText('Taller de primeros auxilios')).toBeVisible();

        await page.getByRole('button', { name: 'Cancelar reserva' }).click();
        await page.getByRole('button', { name: 'Sí, cancelar' }).click();
        await expect(page.getByText('No tenés reservas.')).toBeVisible();
    });

    test('un turno que se pisa con otro reservado avisa del choque', async ({ browser }) => {
        const { page } = await sesionDe(browser, '4000014');

        await page.goto('/app/actividades');
        // Turno 04:14 del taller y turno 04:44 de la estación se pisan (el primero dura una hora).
        await page.getByRole('region', { name: 'Taller de primeros auxilios' }).getByRole('button', { name: /^Apuntarme/ }).first().click();
        await expect(page.getByText(/Te apuntaste a/)).toBeVisible();

        await page.getByRole('region', { name: 'Estación JOTA: radioaficionados' }).getByRole('button', { name: /^Reservar/ }).first().click();
        await expect(page.getByText(/Choca con tu reserva/)).toBeVisible();
    });

    test('en la estación cada horario es de un solo equipo: el otro equipo ya no ve el horario ocupado', async ({ browser }) => {
        const estacion = (page, nombre) => page.getByRole('region', { name: nombre });
        const { page: aguilas } = await sesionDe(browser, '4000001');
        const { page: pumas } = await sesionDe(browser, '4000005');

        await aguilas.goto('/app/actividades');
        await estacion(aguilas, 'Estación JOTA: gran juego de patrullas').getByRole('listitem').first().getByRole('button', { name: /^Reservar/ }).click();
        await expect(aguilas.getByText(/¡Reservado!/)).toBeVisible();

        // El otro equipo no ve el horario ocupado (ni ningún «ocupado por otro equipo»): solo el que sigue libre.
        await pumas.goto('/app/actividades');
        const horarios = estacion(pumas, 'Estación JOTA: gran juego de patrullas').getByRole('listitem');
        await expect(horarios).toHaveCount(1);
        await expect(horarios.first().getByText(/Disponible/)).toBeVisible();
        await expect(pumas.getByText(/Ocupado por otro equipo/)).toHaveCount(0);
    });

    test('un equipo reserva una sola estación a la vez, a cualquier hora, hasta que termine', async ({ browser }) => {
        const { page: compa } = await sesionDe(browser, '4000002'); // misma patrulla (Águilas) que reservó el gran juego
        await compa.goto('/app/actividades');

        // El equipo ya tiene reservada una estación: se ve esa, y las demás (bloqueadas) no se muestran.
        await expect(compa.getByText(/Tu equipo ya tiene reservada «Estación JOTA: gran juego de patrullas»/).first()).toBeVisible();
        await expect(compa.getByRole('region', { name: 'Estación JOTA: gran juego de patrullas' }).getByText('Reservado').first()).toBeVisible();
        await expect(compa.getByRole('region', { name: 'Estación JOTA: radioaficionados' })).toHaveCount(0);

        // Los talleres no cuentan: siguen disponibles.
        await expect(compa.getByRole('region', { name: 'Taller de primeros auxilios' }).getByRole('button', { name: /^Apuntarme/ }).first()).toBeEnabled();
    });

    test('los Rovers reservan la estación como una sola unidad', async ({ browser }) => {
        const { page: uno } = await sesionDe(browser, '4000043');
        const { page: otro } = await sesionDe(browser, '4000044');

        await uno.goto('/app/actividades');
        await expect(uno.getByText(/Reservás para todo tu equipo \(Rovers\)/).first()).toBeVisible();
        await uno.getByRole('region', { name: 'Estación JOTA: radioaficionados' }).getByRole('button', { name: /^Reservar/ }).first().click();
        await expect(uno.getByText(/¡Reservado!/)).toBeVisible();

        // Otro Rover ve la estación reservada por su unidad y no puede reservar otra.
        await otro.goto('/app/actividades');
        await expect(otro.getByText(/Tu equipo ya tiene reservada «Estación JOTA: radioaficionados»/).first()).toBeVisible();
        await expect(otro.getByRole('region', { name: 'Estación JOTA: gran juego de patrullas' })).toHaveCount(0);
        await otro.goto('/app/actividades/reservas');
        await expect(otro.getByText('Estación JOTA: radioaficionados')).toBeVisible();
        await expect(otro.getByText('Para tu equipo')).toBeVisible();
    });

    test('el dirigente ve y cancela la reserva de otra persona', async ({ browser }) => {
        const { page: ana } = await sesionDe(browser, '4000025'); // Caminantes: su dirigente es otro
        const { page: pedro } = await sesionDe(browser, '4000003');
        const { page: dir } = await sesionDe(browser, '4000045');

        await pedro.goto('/app/actividades');
        await pedro.getByRole('region', { name: 'Taller de primeros auxilios' }).getByRole('button', { name: /^Apuntarme/ }).nth(2).click();
        await expect(pedro.getByText(/Te apuntaste a/)).toBeVisible();

        await dir.goto('/dirigente/reservas');
        await expect(dir.getByText('Águilas 3')).toBeVisible();
        await dir.getByRole('button', { name: /Cancelar reserva: Águilas 3/ }).click();
        await dir.getByRole('button', { name: 'Sí, cancelar' }).click();
        await expect(dir.getByText('Águilas 3')).toHaveCount(0);

        await pedro.goto('/app/actividades/reservas');
        await expect(pedro.getByText('Taller de primeros auxilios')).toHaveCount(0);

        // La reserva de otra unidad no aparece en la lista de este dirigente.
        await ana.goto('/app/actividades');
        await ana.getByRole('region', { name: 'Taller de primeros auxilios' }).getByRole('button', { name: /^Apuntarme/ }).first().click();
        await expect(ana.getByText(/Te apuntaste a/)).toBeVisible();
        await dir.goto('/dirigente/reservas');
        await expect(dir.getByText('Yaguaretés 3')).toHaveCount(0);
    });

    test('un taller se cursa en un solo turno: al apuntarse, los otros horarios del taller quedan sin botón', async ({ browser }) => {
        const { page } = await sesionDe(browser, '4000016');

        await page.goto('/app/actividades');
        const taller = page.getByRole('region', { name: 'Taller de primeros auxilios' });
        await taller.getByRole('button', { name: /^Apuntarme/ }).first().click();
        await expect(page.getByText(/Te apuntaste a/)).toBeVisible();

        await expect(taller.getByText(/Ya estás apuntado a este taller/)).toBeVisible();
        const otros = await taller.getByRole('button', { name: /^Apuntarme/ }).all();
        expect(otros.length).toBeGreaterThan(0);
        for (const boton of otros) await expect(boton).toBeDisabled();
    });

    test('con una estación reservada se puede apuntar a un taller que no se pisa; el que se pisa avisa del choque', async ({ browser }) => {
        // Datos propios de esta prueba, así no dependen de los horarios que ocupan las otras: estación de +10 h a +11 h, y un taller
        // con un turno que se pisa (+10 h 30 a +11 h 30) y otro que no (+12 h a +13 h).
        tinker(`$tropa = App\\Models\\Unidad::where("nombre", "Tropa")->first();
            $est = App\\Models\\Actividad::create(["tipo" => App\\Enums\\TipoActividad::EstacionJota, "titulo" => "Estación de horarios", "lugar" => "Cancha", "modalidad" => App\\Enums\\Modalidad::Patrulla, "puntos" => 1]);
            $est->unidades()->attach($tropa); $est->turnos()->create(["inicio" => now()->addHours(10), "fin" => now()->addHours(11)]);
            $tal = App\\Models\\Actividad::create(["tipo" => App\\Enums\\TipoActividad::Taller, "titulo" => "Taller de horarios", "lugar" => "Salón", "modalidad" => App\\Enums\\Modalidad::Individual, "puntos" => 1]);
            $tal->unidades()->attach($tropa);
            $tal->turnos()->create(["inicio" => now()->addHours(10)->addMinutes(30), "fin" => now()->addHours(11)->addMinutes(30), "cupo" => 5]);
            $tal->turnos()->create(["inicio" => now()->addHours(12), "fin" => now()->addHours(13), "cupo" => 5]); echo "ok";`);

        const { page } = await sesionDe(browser, '4000009');
        await page.goto('/app/actividades');

        // El equipo reserva la estación.
        await page.getByRole('region', { name: 'Estación de horarios' }).getByRole('button', { name: /^Reservar/ }).click();
        await expect(page.getByText(/¡Reservado!/)).toBeVisible();

        // El primer turno del taller se pisa con la estación: avisa del choque.
        const taller = page.getByRole('region', { name: 'Taller de horarios' });
        await taller.getByRole('listitem').nth(0).getByRole('button', { name: /^Apuntarme/ }).click();
        await expect(page.getByText(/Choca con/)).toBeVisible();

        // El segundo turno no se pisa: se puede, aunque la estación siga reservada.
        await taller.getByRole('listitem').nth(1).getByRole('button', { name: /^Apuntarme/ }).click();
        await expect(page.getByText(/Te apuntaste a/)).toBeVisible();

        // Las dos cosas aparecen en «Mis reservas».
        await page.goto('/app/actividades/reservas');
        await expect(page.getByText('Estación de horarios')).toBeVisible();
        await expect(page.getByText('Taller de horarios')).toBeVisible();
    });

    test('un taller que ya empezó todavía deja anotarse hasta que termina', async ({ browser }) => {
        // Un taller con un turno que empezó hace 15 minutos y termina en 45.
        tinker(`$tropa = App\\Models\\Unidad::where("nombre", "Tropa")->first();
            $tal = App\\Models\\Actividad::create(["tipo" => App\\Enums\\TipoActividad::Taller, "titulo" => "Taller en curso", "lugar" => "Auditorio", "modalidad" => App\\Enums\\Modalidad::Individual, "puntos" => 1]);
            $tal->unidades()->attach($tropa);
            $tal->turnos()->create(["inicio" => now()->subMinutes(15), "fin" => now()->addMinutes(45), "cupo" => 50]); echo "ok";`);

        const { page } = await sesionDe(browser, '4000010');
        await page.goto('/app/actividades');

        const taller = page.getByRole('region', { name: 'Taller en curso' });
        await expect(taller.getByText(/En curso: todavía podés sumarte/)).toBeVisible();
        await taller.getByRole('button', { name: /^Apuntarme/ }).click();
        await expect(page.getByText(/Te apuntaste a/)).toBeVisible();
    });

    test('la agenda separa «Talleres» y «Estaciones JOTA», con los talleres primero', async ({ browser }) => {
        const { page } = await sesionDe(browser, '4000012');
        await page.goto('/app/actividades');

        const talleres = page.getByRole('heading', { name: 'Talleres', level: 2 });
        const estaciones = page.getByRole('heading', { name: 'Estaciones JOTA', level: 2 });
        await expect(talleres).toBeVisible();
        await expect(estaciones).toBeVisible();

        const yTalleres = (await talleres.boundingBox()).y;
        const yEstaciones = (await estaciones.boundingBox()).y;
        expect(yTalleres).toBeLessThan(yEstaciones);

        // Todo taller queda antes del encabezado de estaciones; toda estación, después.
        const taller = await page.getByRole('region', { name: 'Taller de primeros auxilios' }).boundingBox();
        expect(taller.y).toBeGreaterThan(yTalleres);
        expect(taller.y).toBeLessThan(yEstaciones);
    });
});
