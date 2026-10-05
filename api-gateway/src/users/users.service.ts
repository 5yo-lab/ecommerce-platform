import { ConflictException, Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import * as bcrypt from 'bcryptjs';
import { User, UserRole } from './entities/user.entity';

const BCRYPT_ROUNDS = 12;

@Injectable()
export class UsersService {
  constructor(
    @InjectRepository(User) private readonly users: Repository<User>,
  ) {}

  findById(id: string): Promise<User | null> {
    return this.users.findOne({ where: { id } });
  }

  findByEmail(email: string): Promise<User | null> {
    return this.users.findOne({ where: { email: email.toLowerCase() } });
  }

  async create(input: {
    name: string;
    email: string;
    password: string;
    role?: UserRole;
  }): Promise<User> {
    const email = input.email.toLowerCase();
    if (await this.users.exist({ where: { email }, withDeleted: true })) {
      throw new ConflictException('Email is already registered');
    }

    const user = this.users.create({
      name: input.name,
      email,
      password: await bcrypt.hash(input.password, BCRYPT_ROUNDS),
      role: input.role ?? UserRole.CUSTOMER,
    });
    return this.users.save(user);
  }

  verifyPassword(plain: string, hash: string): Promise<boolean> {
    return bcrypt.compare(plain, hash.replace(/^\$2y\$/, '$2b$'));
  }
}
